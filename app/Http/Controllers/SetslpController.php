<?php namespace App\Http\Controllers;
use DB;
use Event;
use StdClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\DateRange;
use App\Http\Controllers\Controller;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Repositories\SetslpRepository as SetslpRepo;
use App\Repositories\DailySales2Repository as DailySalesRepo;
use App\Repositories\MonthlySalesRepository as MonthlySalesRepo;
//use App\Events\setslp\Change as setslpChange;
//use App\Events\setslp\Delete as setslpDelete;

class SetslpController extends Controller { 

	protected $setslp;
	protected $files;
	protected $ds;
	protected $ms;

	public function __construct(SetslpRepo $setslp, DailySalesRepo $ds, MonthlySalesRepo $ms) {
		$this->setslp = $setslp;
		$this->ds = $ds;
		$this->ms = $ms;
		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
	}

	public function getHistory($brcode, Request $request) {
		
		$setslps = $this->setslp
			//->skipCache()
			->with(['fileUpload'=>function($query){
        $query->select(['filename', 'terminal', 'id']);
      }])
      ->orderBy('created_at', 'DESC')
      ->paginate(10);
				
		return view('docu.setslp.index')->with('setslps', $setslps);
	}

	public function getChecklist($brcode, Request $request) {

		$datas = [];
		$date = carbonCheckorNow($request->input('date'));
		$dss = $this->ds->getByBranchDate($date->copy()->startOfMonth(), $date->copy()->endOfMonth(),  ['date', 'sales', 'sale_chg']);

		//return $this->setslp->monthlyLogs($date);

		foreach ($this->setslp->monthlyLogs($date) as $key => $data) {

			$d = $data['date'];
			$datas[$key]['date'] = $data['date'];
			$datas[$key]['count'] = $data['count'];
			$datas[$key]['slip_total'] = $data['total'];
			$datas[$key]['slips'] = $data['datas'];

			$f = $dss->filter(function ($item) use ($d){
	      return $item->date->format('Y-m-d') == $d->format('Y-m-d')
	      	? $item : null;
	    });

	    $b = $f->first();

			if(is_null($b))
	  		$datas[$key]['pos_total'] = 0;
	  	else
	  		$datas[$key]['pos_total'] = $b->sale_chg;
		
		}
		return view('docu.setslp.checklist')->with('date', $date)->with('datas', $datas);
	}

	public function getAction($brcode, $id=null, $action=null) {
		if($brcode!==strtolower(session('user.branchcode')))
			return redirect($brcode.'/setslp/log');

		if (strtolower($action)==='edit')
			return $this->editsetslp($id);
		elseif (is_uuid($id) && is_null($action))
			return $this->viewsetslp($id);

		if (strtolower($action)==='edit' && is_uuid($id))
			return $this->editsetslp($id);
		elseif (is_uuid($id) && is_null($action))
			return $this->viewsetslp($id);
		else
			return $this->getsetslpFileSystem($brcode, $id, $action);
	}


	private function getsetslpFileSystem($brcode, $id, $action) { // $id = yr, $action = month

		$paths = [];

		$r = $this->files->folderInfo2('SETSLP');

		foreach ($r['subfolders'] as $path => $folder) {
			if ($this->files->exists($path.DS.strtoupper($brcode)))
				$paths[$path.'/'.strtoupper($brcode)] = $folder;
		}
		
		$y = $this->files->folderInfo2(array_search($id, $paths));

		if (in_array($id, $paths) && is_null($action))  {
			$data = [
					'folder' 			=> "/SETSLP/".$id,
					'folderName'  => $id,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/setslp'   => "SETSLP",
					],
					'subfolders' 	=> $y['subfolders'],
					'files' 			=> $y['files']
				];
		} elseif (in_array($id, $paths) && in_array($action, $y['subfolders']))  {
			$m = $this->files->folderInfo2(array_search($action, $y['subfolders']));
			$data = [
					'folder' 			=> "/SETSLP/".$id.'/'.$action,
					'folderName'  => $action,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/setslp'   => "SETSLP",
						'/setslp/'.$id   => $id,
					],
					'subfolders' 	=> $m['subfolders'],
					'files' 			=> $m['files']
				];
		} elseif (is_null($id) && is_null($action))  {
			//$data = $r;
			$data = [
					'folder' 			=> "/SETSLP",
					'folderName'  => "SETSLP",
					'breadcrumbs' => [
						'/' 				=> "Storage",
					],
					'subfolders' 	=> $paths,
					'files' 			=> []
				];
		} else 
			return abort('404');
	
		
		return view('docu.setslp.filelist')->with('data', $data);


	}


	private function verify($id, $userid, $matched=0) {
		return $this->setslp->update([
			'verified' 	=> 1,
			'matched'		=> $matched,
			'user_id'		=> $userid,
			'updated_at' 	=> c()
		], $id);
	}

	private function checkVerify($id) {

		if(request()->has('user_id') && is_uuid(request()->input('user_id')))
			$userid = strtoupper(request()->input('user_id'));
		else
			$userid = strtoupper(request()->user()->id);

		if(request()->has('verified') && request()->input('verified')==true)
			return $this->verify($id, '41F0FB56DFA811E69815D19988DDBE1E');
		else if(request()->has('verify') && request()->input('verify')==true)
			return $this->verify($id, $userid);
		else
			return false;
	}



	private function viewsetslp($id) {
		$setslp = $this->setslp->find($id);
		if(!$setslp->verified)
			if($this->checkVerify($setslp->id))
				return $this->viewsetslp($id);
		return view('docu.setslp.view', compact('setslp'));
	}

	private function editsetslp($id) {
		$setslp = $this->setslp->find($id);
		if($setslp->verified || $setslp->matched)
			return $this->viewsetslp($id);
		return view('docu.setslp.edit', compact('setslp'));
	}

	public function getImage(Request $request, $brcode, $filename) {

		$id = explode('.', $filename);

		if(!is_uuid($id[0]) || $brcode!==strtolower(session('user.branchcode')))
			return abort(404);

		$d = $this->setslp->find($id[0]);

		$path = $this->getPath($d);

		if(!$this->files->exists($this->getPath($d)))
			return abort(404);

		if($request->has('download') && $request->input('download')==='true') {
    	return response($this->files->get($path), 200)
	 						->header('Content-Type', $this->files->fileMimeType($path))
  						->header('Content-Disposition', 'attachment; filename="'.$d->filename.'"');
		}

		return response($this->files->get($path), 200)
	 						->header('Content-Type', $this->files->fileMimeType($path));

	}

	

	private function countFilenameByDate($date, $time, $type) {
  	$d = $this->setslp->findWhere(['date'=>$date, 'time'=>$time, 'terminal_id'=>$type]);
		$c = intval(count($d));
  	if ($c>1)
			return $c;
		return false;
  }

  private function moveUpdatedFile($o, $n) {
  	if ($o->date!=$n->date || $o->time!=$n->time || $o->terminal_id!=$n->terminal_id) {
			
			$old_path = 'SETSLP'.DS.$o->date->format('Y').DS.session('user.branchcode').DS.$o->date->format('m').DS.$o->filename;
			$ext = strtolower(pathinfo($o->filename, PATHINFO_EXTENSION));
			$br = strtoupper(session('user.branchcode'));
			switch ($n->terminal_id) {
				case 1:
					$type = 'BDO';
					break;
				case 2:
					$type = 'RCBC';
					break;	
				case 3:
					$type = 'HSBC';
					break;				
				default:
					$type = 'X';
					break;
			}
			
			if ($this->files->exists($old_path)) {
				$date = carbonCheckorNow($n->date->format('Y-m-d').' '.$n->time);

				$cnt = $this->countFilenameByDate($date->format('Y-m-d'), $date->format('H:i:s'), $n->terminal_id);
				if ($cnt)
					$filename = 'SETSLP '.$br.' '.$date->format('Ymd His').' '.$type.'-'.$cnt.'.'.$ext;
				else
					$filename = 'SETSLP '.$br.' '.$date->format('Ymd His').' '.$type.'.'.$ext;

				$new_path = 'SETSLP'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$filename; 

				try {
	     		$this->files->moveFile($this->files->realFullPath($old_path), $new_path, true); // false = override file!
		    } catch(Exception $e) {
					return false;
		    }
				return $filename;
			}
			return false;
		} else
			return false;
  }

	public function put(Request $request) {
		
		$rules = [
			'date'		=> 'required|date',
			'time'		=> 'required',
			'amount'	=> 'required',
			'cashier'	=> 'required',
			'terminal_id'	=> 'required',
			'id'			=> 'required',
		];

		$validator = app('validator')->make($request->all(), $rules);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);
		
		$o = $this->setslp->find($request->input('id'));
		if(!is_null($o)) {
			
			$d = $this->setslp->update([
				'date' 				=> request()->input('date'),
	    	'time' 				=> request()->input('time'),
	    	'terminal_id' => request()->input('terminal_id'),
	    	'amount' 			=> str_replace(",", "", request()->input('amount')),
	    	'cashier' 		=> $request->input('cashier'),
	    	'remarks' 		=> $request->input('notes'),
	    	'updated_at' 	=> c()
			], $o->id);


			$filename = $this->moveUpdatedFile($o, $d);
			if ($filename!==false) {
				$d = $this->setslp->update([
					'filename'		=> $filename,
		    	'updated_at' 	=> c()
				], $o->id);
			}

			$arr = array_diff($o->toArray(), $d->toArray());
			array_forget($arr, 'updated_at');
			
			//if (app()->environment()==='production')
				//event(new setslpChange($o, $d, $arr));

			return redirect(brcode().'/setslp/'.$d->lid())
							->with('alert-success', 'Card settlement slip is updated!');
		}

		return redirect()->back()->withErrors('Card settlement not found!');
	}



	private function getPath($d) {
		return 'SETSLP'.DS.$d->date->format('Y').DS.session('user.branchcode').DS.$d->date->format('m').DS.$d->filename;
	}

	public function delete(Request $request) {
		
		$validator = app('validator')->make($request->all(), ['id'=>'required'], []);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);

		$setslp = $this->setslp->find($request->input('id'));

		if (is_null($setslp))
			return redirect()->back()->withErrors('Card settlement slip not found!');

		if (!$setslp->isDeletable())
			return redirect()->back()->withErrors($setslp->fileUpload->filename.' deposit slip is not deletable, already verified!');

		if ($this->setslp->delete($setslp->id)) {
			
			if ($this->files->exists($this->getPath($setslp)))
				$this->files->deleteFile($this->getPath($setslp));

			//if (app()->environment()==='production')
				//event(new setslpDelete($setslp->toArray()));

			return redirect(brcode().'/setslp/log')
							->with('setslp.delete', $setslp)
							->with('alert-important', true);
		}
		return redirect()->back()->withErrors('Error while deleting record!');
	}


	public function aggregate($setslp) {

		$ds = $this->setslp->sumByBizdate($setslp->bizdate);

		DB::beginTransaction();

		try {
			$this->ds->firstOrNewField([
	    	'branchid'  => $setslp->branch_id,
	    	'date'      => $setslp->bizdate->format('Y-m-d'),
	    	'setslp'    => $ds->amount
	  	], ['date', 'branchid']);
		} catch (Exception $e) {
			DB::rollBack();
			throw $e;			
		}

		$ms = $this->ds->sumFieldsByMonth(['setslp'], $setslp->bizdate);

		try {
			$this->ms->firstOrNewField([
	    	'branch_id'  => $setslp->branch_id,
	    	'date'      => $setslp->bizdate->copy()->lastOfMonth()->format('Y-m-d'),
	    	'setslp'    => $ms->setslp
	  	], ['date', 'branch_id']);
		} catch (Exception $e) {
			DB::rollBack();
			throw $e;			
		}

		DB::commit();
    

	}





	






}