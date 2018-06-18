<?php namespace App\Http\Controllers;
use Event;
use StdClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\DateRange;
use App\Http\Controllers\Controller;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Repositories\DepslipRepository as DepslpRepo;
use App\Events\Depslp\Change as DepslpChange;
use App\Events\Depslp\Delete as DepslpDelete;

class DepslpController extends Controller { 

	protected $depslip;
	protected $files;

	public function __construct(DepslpRepo $depslip) {
		$this->depslip = $depslip;
		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
	}

	public function getHistory($brcode, Request $request) {
		
		$depslips = $this->depslip
			//->skipCache()
			->with(['fileUpload'=>function($query){
        $query->select(['filename', 'terminal', 'id']);
      }])
      ->orderBy('created_at', 'DESC')
      ->paginate(10);
				
		return view('docu.depslp.index')->with('depslips', $depslips);
	}

	public function getChecklist($brcode, Request $request) {

		$date = carbonCheckorNow($request->input('date'));
		$depslips = $this->depslip->monthlyLogs($date);

		return view('docu.depslp.checklist')->with('date', $date)->with('depslips', $depslips);
	}

	public function getAction($brcode, $id=null, $action=null) {
		if($brcode!==strtolower(session('user.branchcode')))
			return redirect($brcode.'/depslp/log');

		if (strtolower($action)==='edit')
			return $this->editDepslp($id);
		elseif (is_uuid($id) && is_null($action))
			return $this->viewDepslp($id);

		if (strtolower($action)==='edit' && is_uuid($id))
			return $this->editDepslp($id);
		elseif (is_uuid($id) && is_null($action))
			return $this->viewDepslp($id);
		else
			return $this->getDepslpFileSystem($brcode, $id, $action);
	}


	private function getDepslpFileSystem($brcode, $id, $action) { // $id = yr, $action = month

		$paths = [];

		$r = $this->files->folderInfo2('DEPSLP');

		foreach ($r['subfolders'] as $path => $folder) {
			if ($this->files->exists($path.DS.strtoupper($brcode)))
				$paths[$path.'/'.strtoupper($brcode)] = $folder;
		}
		
		$y = $this->files->folderInfo2(array_search($id, $paths));

		if (in_array($id, $paths) && is_null($action))  {
			$data = [
					'folder' 			=> "/DEPSLP/".$id,
					'folderName'  => $id,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/DEPSLP'   => "DEPSLP",
					],
					'subfolders' 	=> $y['subfolders'],
					'files' 			=> $y['files']
				];
		} elseif (in_array($id, $paths) && in_array($action, $y['subfolders']))  {
			$m = $this->files->folderInfo2(array_search($action, $y['subfolders']));
			$data = [
					'folder' 			=> "/DEPSLP/".$id.'/'.$action,
					'folderName'  => $action,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/DEPSLP'   => "DEPSLP",
						'/DEPSLP/'.$id   => $id,
					],
					'subfolders' 	=> $m['subfolders'],
					'files' 			=> $m['files']
				];
		} elseif (is_null($id) && is_null($action))  {
			//$data = $r;
			$data = [
					'folder' 			=> "/DEPSLP",
					'folderName'  => "DEPSLP",
					'breadcrumbs' => [
						'/' 				=> "Storage",
					],
					'subfolders' 	=> $paths,
					'files' 			=> []
				];
		} else 
			return abort('404');
	
		
		return view('docu.depslp.filelist')->with('data', $data);


	}


	private function verify($id, $userid, $matched=0) {
		return $this->depslip->update([
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



	private function viewDepslp($id) {
		$depslp = $this->depslip->find($id);
		if(!$depslp->verified)
			if($this->checkVerify($depslp->id))
				return $this->viewDepslp($id);
		return view('docu.depslp.view', compact('depslp'));
	}

	private function editDepslp($id) {
		$depslp = $this->depslip->find($id);
		if($depslp->verified || $depslp->matched)
			return $this->viewDepslp($id);
		return view('docu.depslp.edit', compact('depslp'));
	}

	public function getImage(Request $request, $brcode, $filename) {

		$id = explode('.', $filename);

		if(!is_uuid($id[0]) || $brcode!==strtolower(session('user.branchcode')))
			return 'not uuid';

		$d = $this->depslip->find($id[0]);

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
  	$d = $this->depslip->findWhere(['date'=>$date, 'time'=>$time, 'type'=>$type]);
		$c = intval(count($d));
  	if ($c>1)
			return $c;
		return false;
  }

  private function moveUpdatedFile($o, $n) {
  	if ($o->date!=$n->date || $o->time!=$n->time || $o->type!=$n->type) {
			
			$old_path = 'DEPSLP'.DS.$o->date->format('Y').DS.session('user.branchcode').DS.$o->date->format('m').DS.$o->filename;
			$ext = strtolower(pathinfo($o->filename, PATHINFO_EXTENSION));
			$br = strtoupper(session('user.branchcode'));
			switch ($n->type) {
				case 1:
					$type = 'C';
					break;
				case 2:
					$type = 'K';
					break;				
				default:
					$type = 'U';
					break;
			}
			
			if ($this->files->exists($old_path)) {
				$date = carbonCheckorNow($n->date->format('Y-m-d').' '.$n->time);

				$cnt = $this->countFilenameByDate($date->format('Y-m-d'), $date->format('H:i:s'), $n->type);
				if ($cnt)
					$filename = 'DEPSLP '.$br.' '.$date->format('Ymd His').' '.$type.'-'.$cnt.'.'.$ext;
				else
					$filename = 'DEPSLP '.$br.' '.$date->format('Ymd His').' '.$type.'.'.$ext;

				$new_path = 'DEPSLP'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$filename; 

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
			'id'			=> 'required',
		];

		$validator = app('validator')->make($request->all(), $rules);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);
		
		$o = $this->depslip->find($request->input('id'));
		if(!is_null($o)) {
			
			$d = $this->depslip->update([
				'date' 				=> request()->input('date'),
	    	'time' 				=> request()->input('time'),
	    	'type' 				=> request()->input('type'),
	    	'amount' 			=> str_replace(",", "", request()->input('amount')),
	    	'cashier' 		=> $request->input('cashier'),
	    	'remarks' 		=> $request->input('notes'),
	    	'updated_at' 	=> c()
			], $o->id);


			$filename = $this->moveUpdatedFile($o, $d);
			if ($filename!==false) {
				$d = $this->depslip->update([
					'filename'		=> $filename,
		    	'updated_at' 	=> c()
				], $o->id);
			}

			$arr = array_diff($o->toArray(), $d->toArray());
			array_forget($arr, 'updated_at');
			
			if (app()->environment()==='production')
				event(new DepslpChange($o, $d, $arr));

			return redirect(brcode().'/depslp/'.$d->lid())
							->with('alert-success', 'Deposit slip is updated!');
		}

		return redirect()->back()->withErrors('Deposit Slip not found!');
	}



	private function getPath($d) {
		return 'DEPSLP'.DS.$d->date->format('Y').DS.session('user.branchcode').DS.$d->date->format('m').DS.$d->filename;
	}

	public function delete(Request $request) {

		$validator = app('validator')->make($request->all(), ['id'=>'required'], []);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);

		$depslp = $this->depslip->find($request->input('id'));

		if (is_null($depslp))
			return redirect()->back()->withErrors('Deposit slip not found!');

		if (!$depslp->isDeletable())
			return redirect()->back()->withErrors($depslp->fileUpload->filename.' deposit slip is not deletable, already verified!');

		if ($this->depslip->delete($depslp->id)) {
			
			if ($this->files->exists($this->getPath($depslp)))
				$this->files->deleteFile($this->getPath($depslp));

			//if (app()->environment()==='production')
				event(new DepslpDelete($depslp->toArray()));

			return redirect(brcode().'/depslp/log')
							->with('depslp.delete', $depslp)
							->with('alert-important', true);
		}
		return redirect()->back()->withErrors('Error while deleting record!');
	}






	






}