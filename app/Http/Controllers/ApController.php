<?php namespace App\Http\Controllers;
use Event;
use StdClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\DateRange;
use App\Http\Controllers\Controller;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Repositories\FileUploadRepository as FileUploadRepo;


class ApController extends Controller { 

	protected $fileUpload;
	protected $files;
	protected $filetype_id = '11E775BF8F29696AD5F13842A0DEEA4D'; // ap
	protected $user_id = '11E775C18F29696AD5F13842AC687868'; // fmarquez

	public function __construct(FileUploadRepo $fileUpload) {
		$this->fileUpload = $fileUpload;
		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
	}

	public function getHistory($brcode, Request $request) {
		
		$aps = $this->fileUpload
			->skipCache()
     	->scopeQuery(function($query){
			    return $query->where('filetype_id', '11E775BF8F29696AD5F13842A0DEEA4D');
			})
			->orderBy('uploaddate', 'desc')
      ->paginate(10);
				
		return view('docu.ap.index')->with('aps', $aps);
	}

	public function getChecklist($brcode, Request $request) {

		$date = carbonCheckorNow($request->input('date'));
		$depslips = $this->depslip->monthlyLogs($date);

		return view('docu.AP.checklist')->with('date', $date)->with('depslips', $depslips);
	}

	public function getAction($brcode, $id=null, $action=null, $day=null) {
		if($brcode!==strtolower(session('user.branchcode')))
			return redirect($brcode.'/ap/log');

		if (strtolower($action)==='edit')
			return $this->editAP($id);
		elseif (is_uuid($id) && is_null($action))
			return $this->viewAP($id);

		if (strtolower($action)==='edit' && is_uuid($id))
			return $this->editAP($id);
		elseif (is_uuid($id) && is_null($action))
			return $this->viewAP($id);
		else
			return $this->getApFileSystem($brcode, $id, $action, $day);
	}


	private function getApFileSystem($brcode, $id, $action, $day) { // $id = yr, $action = month, $day

		$paths = [];

		$r = $this->files->folderInfo2('AP');

		foreach ($r['subfolders'] as $path => $folder) {
			if ($this->files->exists($path.DS.strtoupper($brcode)))
				$paths[$path.'/'.strtoupper($brcode)] = $folder;
		}
		
		$y = $this->files->folderInfo2(array_search($id, $paths));

		if (in_array($id, $paths) && is_null($action) && is_null($day))  {
			$data = [
					'folder' 			=> "/AP/".$id,
					'folderName'  => $id,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/AP'   		=> "Payables",
					],
					'subfolders' 	=> $y['subfolders'],
					'files' 			=> $y['files']
				];
		} elseif (in_array($id, $paths) && in_array($action, $y['subfolders']))  {
			$m = $this->files->folderInfo2(array_search($action, $y['subfolders']));

			if (is_null($day)) {

				$data = [
					'folder' 			=> "/AP/".$id.'/'.$action,
					'folderName'  => $action,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/AP'   		=> "Payables",
						'/AP/'.$id  => $id,
					],
					'subfolders' 	=> $m['subfolders'],
					'files' 			=> $m['files']
				];
			} else {
				$d = $this->files->folderInfo2(array_search($day, $m['subfolders']));
				$data = [
					'folder' 			=> "/AP/".$id.'/'.$action.'/'.$day,
					'folderName'  => $day,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/AP'   		=> "Payables",
						'/AP/'.$id  => $id,
						'/AP/'.$id.'/'.$action   => $action,
					],
					'subfolders' 	=> $d['subfolders'],
					'files' 			=> $d['files']
				];
			}

		} elseif (is_null($id) && is_null($action) && is_null($action))  {
			//$data = $r;
			$data = [
					'folder' 			=> "/AP",
					'folderName'  => "Payables",
					'breadcrumbs' => [
						'/' 				=> "Storage",
					],
					'subfolders' 	=> $paths,
					'files' 			=> []
				];
		} else 
			return abort('404');
		
		//return $data;
		return view('docu.ap.filelist')->with('data', $data);
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



	private function viewAP($id) {
		$AP = $this->depslip->find($id);
		if(!$AP->verified)
			if($this->checkVerify($AP->id))
				return $this->viewAP($id);
		return view('docu.AP.view', compact('AP'));
	}

	private function editAP($id) {
		$AP = $this->depslip->find($id);
		if($AP->verified || $AP->matched)
			return $this->viewAP($id);
		return view('docu.AP.edit', compact('AP'));
	}

	public function getImage(Request $request, $brcode, $filename) {

		$id = explode('.', $filename);

		if(!is_uuid($id[0]) || $brcode!==strtolower(session('user.branchcode')))
			return abort(404);

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

	private function countFilenameByDate($date, $time) {
  	$d = $this->depslip->findWhere(['date'=>$date, 'time'=>$time]);
		$c = intval(count($d));
  	if ($c>1)
			return $c+1;
		return false;
  }

  private function moveUpdatedFile($o, $n) {
  	if ($o->date!=$n->date || $o->time!=$n->time) {
			
			$old_path = 'AP'.DS.$o->date->format('Y').DS.session('user.branchcode').DS.$o->date->format('m').DS.$o->filename;
			$ext = strtolower(pathinfo($o->filename, PATHINFO_EXTENSION));
			$br = strtoupper(session('user.branchcode'));
			
			if ($this->files->exists($old_path)) {
				$date = carbonCheckorNow($n->date->format('Y-m-d').' '.$n->time);

				$cnt = $this->countFilenameByDate($date->format('Y-m-d'), $date->format('H:i:s'));
				if ($cnt)
					$filename = 'AP '.$br.' '.$date->format('Ymd His').'-'.$cnt.'.'.$ext;
				else
					$filename = 'AP '.$br.' '.$date->format('Ymd His').'.'.$ext;

				$new_path = 'AP'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$filename; 

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
			'date'				=> 'required|date',
			'time'				=> 'required',
			'amount'			=> 'required',
			'cashier'			=> 'required',
			'id'				=> 'required',
		];

		$validator = app('validator')->make($request->all(), $rules);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);
		
		$o = $this->depslip->find($request->input('id'));
		if(!is_null($o)) {
			
			$d = $this->depslip->update([
			'date' 			=> request()->input('date'),
	    	'time' 			=> request()->input('time'),
	    	'amount' 		=> str_replace(",", "", request()->input('amount')),
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
				event(new APChange($o, $d, $arr));

			return redirect(brcode().'/AP/'.$d->lid())
							->with('alert-success', 'Deposit slip is updated!');
		}

		return redirect()->back()->withErrors('Deposit Slip not found!');
	}



	private function getPath($d) {
		return 'AP'.DS.$d->date->format('Y').DS.session('user.branchcode').DS.$d->date->format('m').DS.$d->filename;
	}

	public function delete(Request $request) {

		$validator = app('validator')->make($request->all(), ['id'=>'required'], []);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);

		$AP = $this->depslip->find($request->input('id'));

		if (is_null($AP))
			return redirect()->back()->withErrors('Deposit slip not found!');

		if (!$AP->isDeletable())
			return redirect()->back()->withErrors($AP->fileUpload->filename.' deposit slip is not deletable, already verified!');

		if ($this->depslip->delete($AP->id)) {
			
			if ($this->files->exists($this->getPath($AP)))
				$this->files->deleteFile($this->getPath($AP));

			//if (app()->environment()==='production')
				event(new APDelete($AP->toArray()));

			return redirect(brcode().'/AP/log')
							->with('AP.delete', $AP)
							->with('alert-important', true);
		}
		return redirect()->back()->withErrors('Error while deleting record!');
	}

	

	public function getDownload(Request $request, $p1=null, $p2=null, $p3=null, $p4=null, $p5=null, $p6=null) {

		if(is_null($p2) || is_null($p2) || is_null($p3) || is_null($p4) || is_null($p5) || is_null($p6))
    	return abort('404');

    $path = $p1.'/'.$p2.'/'.$p3.'/'.$p4.'/'.$p5.'/'.$p6;

    if ($request->has('m'))
    	$m = 'log';
    else
    	$m = 'fs';

    //if (!in_array($request->user()->username, ['jrsalunga', 'admin']))
			logAction('ap:download', 'user:'.$request->user()->username.' | '.$p6.' | '.$m.' | ');

		try {
		
			$file = $this->files->get($path);
			$mimetype = $this->files->fileMimeType($path);

	    $response = \Response::make($file, 200);
		 	$response->header('Content-Type', $mimetype);
	  	
	  	//if ($request->has('download') && $request->input('download')=='true')
	  	$response->header('Content-Disposition', 'attachment; filename="'.$p6.'"');

		  return $response;
		} catch (\Exception $e) {
			return abort('404');
		}
	}






	






}