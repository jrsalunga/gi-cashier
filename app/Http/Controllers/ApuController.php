<?php namespace App\Http\Controllers;
use Event;
use Exception;
use Response;
use StdClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\DateRange;
use App\Http\Controllers\Controller;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Repositories\ApUploadRepository as ApUploadRepo;
use App\Events\Notifier;

class ApuController extends Controller { 

	protected $repo;
	protected $files;
	protected $filetype_id = '11EAAB281C1B0D85A7E0E521BE29B63C'; // ap
	protected $user_id = '11E775C18F29696AD5F13842AC687868'; // fmarquez

	public function __construct(ApUploadRepo $repo) {
		$this->repo = $repo;
		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
	}

	public function getHistory($brcode, Request $request) {

		$apus = $this->repo
			->skipCache()
     	->with(['fileUpload'=>function($query){ $query->select(['filename', 'terminal', 'id']); }])
      ->with(['supplier'=>function($query){ $query->select(['code', 'descriptor', 'id']); }])
      ->with(['doctype'=>function($query){ $query->select(['code', 'descriptor', 'id']); }])
			->orderBy('created_at', 'desc')
      ->paginate(10);

		return view('docu.apu.index')->with('apus', $apus);
	}

	public function getChecklist($brcode, Request $request) {

		$data = [];
		$date = carbonCheckorNow($request->input('date'));
  	$fr = $date->firstOfMonth();
  	$to = $date->copy()->lastOfMonth();

  	foreach (dateInterval($fr->format('Y-m-d'), $to->format('Y-m-d')) as $key => $d) {

  		$dir = 'AP'.DS.$d->format('Y').DS.session('user.branchcode').DS.$d->format('m').DS.$d->format('d');

  		$data[$key]['date'] = $d;

  		if ($this->files->exists($dir)) {
  			$data[$key]['exist'] = true;
  			$data[$key]['file_count'] = $this->files->fileCount($this->files->realFullPath($dir));  			
  			$data[$key]['uri'] = '/'.brcode().'/ap/'.$d->format('Y/m/d');
  		} else {
  			$data[$key]['exist'] = false;
  		}
  	}

		return view('docu.ap.checklist')->with('date', $date)->with('data', $data);
	}

	public function getAction($brcode, $id=null, $action=null, $day=null) {
		if($brcode!==strtolower(session('user.branchcode')))
			return redirect($brcode.'/apu/log');

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
		$r = $this->files->folderInfo2('APU');

		foreach ($r['subfolders'] as $path => $folder) {
			if ($this->files->exists($path.DS.strtoupper($brcode)))
				$paths[$path.'/'.strtoupper($brcode)] = $folder;
		}
		
		$y = $this->files->folderInfo2(array_search($id, $paths));

		if (in_array($id, $paths) && is_null($action) && is_null($day))  {
			$data = [
					'folder' 			=> "/APU/".$id,
					'folderName'  => $id,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/APU'   		=> "Payables",
					],
					'subfolders' 	=> $y['subfolders'],
					'files' 			=> $y['files']
				];
		} elseif (in_array($id, $paths) && in_array($action, $y['subfolders']))  {
			$m = $this->files->folderInfo2(array_search($action, $y['subfolders']));

			if (is_null($day)) {
				$data = [
					'folder' 			=> "/APU/".$id.'/'.$action,
					'folderName'  => $action,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/APU'   		=> "Payables",
						'/APU/'.$id  => $id,
					],
					'subfolders' 	=> $m['subfolders'],
					'files' 			=> $m['files']
				];
			} else {
				$d = $this->files->folderInfo2(array_search($day, $m['subfolders']));
				$data = [
					'folder' 			=> "/APU/".$id.'/'.$action.'/'.$day,
					'folderName'  => $day,
					'breadcrumbs' => [
						'/' 				=> "Storage",
						'/APU'   		=> "Payables",
						'/APU/'.$id  => $id,
						'/APU/'.$id.'/'.$action   => $action,
					],
					'subfolders' 	=> $d['subfolders'],
					'files' 			=> $d['files']
				];
			}
		} elseif (is_null($id) && is_null($action) && is_null($action))  {
			$data = [
					'folder' 			=> "/APU",
					'folderName'  => "Payables",
					'breadcrumbs' => [
						'/' 				=> "Storage",
					],
					'subfolders' 	=> $paths,
					'files' 			=> []
				];
		} else 
			return abort('404');

		if (app()->environment()==='production')
			if (request()->input('src')=='email')
    		event(new Notifier(session('user.fullname').' accessed Payables Storage via Email'));

		return view('docu.apu.filelist')->with('data', $data);
	}

	private function verify($id, $userid, $matched=0) {
		return $this->repo->update([
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

	private function viewAp($id) {
		$apu = $this->repo->skipCache()->with(['supplier','doctype','fileupload'])->find($id);
		if(!$apu->verified)
			if($this->checkVerify($apu->id))
				return $this->viewAp($id);
		return view('docu.apu.view', compact('apu'));
	}

	private function editAP($id) {
		$apu = $this->repo->skipCache()->find($id);
		if($apu->verified || $apu->matched)
			return $this->viewAP($id);
		return view('docu.apu.edit', compact('apu'));
	}

	public function getImage(Request $request, $brcode, $filename) {

		$id = explode('.', $filename);

		if(!is_uuid($id[0]) || $brcode!==strtolower(session('user.branchcode')))
			return abort(404);

		$d = $this->repo->find($id[0]);

		$path = $this->getPath($d);

		if (!$this->files->exists($this->getPath($d)))
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
  	$d = $this->repo->findWhere(['date'=>$date]);
		$c = intval(count($d));
  	if ($c>1)
			return $c+1;
		return false;
  }

  private function moveUpdatedFile($o, $n) {
			
		$old_path = 'APU'.DS.$o->date->format('Y').DS.session('user.branchcode').DS.$o->date->format('m').DS.$o->filename;
		$ext = strtolower(pathinfo($o->filename, PATHINFO_EXTENSION));
		$br = strtoupper(session('user.branchcode'));
		
		if ($this->files->exists($old_path)) {
			$date = carbonCheckorNow($n->date->format('Y-m-d'));

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

      if (empty($n->doctype->code)) {
        $document_code = filter_filename($n->doctype->descriptor);
        $document_code = strtoupper(mb_ereg_replace("([\.]{2,})", '', $document_code));
      } else 
        $document_code = strtoupper($n->doctype->code);
        
      $filename = $document_code.' '.$br.' '.$date->format('Ymd').' '.$type.' '.$n->refno.'.'.$ext;
  		$new_path = 'APU'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$filename; 

  		try {
     		$this->files->moveFile($this->files->realFullPath($old_path), $new_path, true); // false = override file!
      } catch(Exception $e) {
  			return false;
      }
  		return $filename;
    }
		return false;
  }

	public function put(Request $request) {
    // return $request->all();
    $rules = [
      'doctype'     => 'required',
      'refno'       => 'required',
      'type'        => 'required',
      'date'        => 'required|date',
      'supplier'    => 'required',
      'cashier'     => 'required',
      'id'          => 'required',
    ];

    $messages = [];
    $validator = app('validator')->make($request->all(), $rules, $messages);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);
		
		$o = $this->repo->find($request->input('id'));
		if (!is_null($o)) {

      $doctype = NULL;
      if ($request->has('doctype') && $request->has('doctypeid'))
        $doctype = \App\Models\Doctype::find($request->input('doctypeid'));
      if ($request->has('doctype') && !$request->has('doctypeid'))
        $doctype = \App\Models\Doctype::create(['descriptor'=>$request->input('doctype'), 'assigned'=>1 ,'branch_id'=>$request->user()->branchid]);
      if (is_null($doctype))
        return redirect()->back()->withErrors(['error'=>'Could not create Doctype for AP Files.']);
    
      if (empty($doctype->code)) {
        $document_code = filter_filename($doctype->descriptor);
        $document_code = strtoupper(mb_ereg_replace("([\.]{2,})", '', $document_code));
      } else 
        $document_code = strtoupper($doctype->code);


      $supplier = NULL;
      if ($request->has('supplier') && $request->has('supplierid'))
        $supplier = \App\Models\Supplier::where('id', $request->input('supplierid'))->first();
      if ($request->has('supplier') && !$request->has('supplierid'))
        $supplier = \App\Models\Supplier::create(['descriptor'=>$request->input('supplier'), 'branchid'=>$request->user()->branchid]);
      if (is_null($supplier))
        return redirect()->back()->withErrors(['error'=>'Could not create Supplier for AP Files.']);

      if (empty($supplier->code)) {
        $supp_filename = filter_filename($supplier->descriptor);
        $supp_filename = strtoupper(mb_ereg_replace("([\.]{2,})", '', $supp_filename));
      } else 
        $supp_filename = strtoupper($supplier->code);
			
			$d = $this->repo->update([
			  // 'doctype' 		=> $doctype->descriptor,
        'doctype_id'  => $doctype->id,
        // 'supplier'    => $supplier->descriptor,
        'supplier_id' => $supplier->id,
	    	'refno' 			=> request()->input('refno'),
        'type'        => request()->input('type'),
        'date'        => request()->input('date'),
	    	'amount' 		  => str_replace(",", "", request()->input('amount')),
	    	'cashier' 		=> $request->input('cashier'),
	    	'remarks' 		=> $request->input('notes'),
	    	'updated_at' 	=> c()
			], $o->id);

			$filename = $this->moveUpdatedFile($o, $d);

			if ($filename!==false) {
				$d = $this->repo->update([
					'filename'		=> $filename,
		    	'updated_at' 	=> c()
				], $o->id);
			}

      // clear certain fields to check the diff of 2 models
      unset($d->doctype);
      unset($d->updated_at);
      unset($o->updated_at);
			$arr = array_diff($o->toArray(), $d->toArray());
      $cnt = count($arr);
			array_forget($arr, 'updated_at');
			
      if ($cnt>0) {
        if (app()->environment()==='production')
				  event(new \App\Events\Update\ApUpload($o, $d, $arr));
      }

			return redirect(brcode().'/apu/'.$d->lid())
							->with('alert-success', 'Accounts payable is updated!');
		} // end: !is_null

		return redirect()->back()->withErrors('Accounts payable not found!');
	}

	private function getPath($d) {
		return 'APU'.DS.$d->date->format('Y').DS.session('user.branchcode').DS.$d->date->format('m').DS.$d->filename;
	}

	public function delete(Request $request) {

		$validator = app('validator')->make($request->all(), ['id'=>'required'], []);
		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);

		$apu = $this->repo->find($request->input('id'));

		if (is_null($apu))
			return redirect()->back()->withErrors('Accounts payable not found!');

		if (!$apu->isDeletable())
			return redirect()->back()->withErrors($ap->fileUpload->filename.' Accounts payable cannot be deleted, already verified!');

		if ($this->repo->delete($apu->id)) {
			if ($this->files->exists($this->getPath($apu)))
				$this->files->deleteFile($this->getPath($apu));

			if (app()->environment()==='production')
				event(new \App\Events\Delete\ApUpload($apu));

			return redirect(brcode().'/apu/log')
							->with('apu.delete', $apu)
							->with('alert-important', true);
		}
		return redirect()->back()->withErrors('Error while deleting record!');
	}

	

	// public function getDownload(Request $request, $p1=null, $p2=null, $p3=null, $p4=null, $p5=null, $p6=null) {

 //    return $path = $p1.'/'.$p2.'/'.$p3.'/'.$p4.'/'.$p5.'/'.$p6;
		
 //    if(is_null($p2) || is_null($p2) || is_null($p3) || is_null($p4) || is_null($p5) || is_null($p6))
 //    	return abort('404');


 //    if ($request->has('m'))
 //    	$m = 'log';
 //    else
 //    	$m = 'fs';

 //    //if (!in_array($request->user()->username, ['jrsalunga', 'admin']))
	// 		logAction($p6, 'user:'.$request->user()->username.' | ap:dl | '.$m.' | ');

	// 	try {
		
	// 		$file = $this->files->get($path);
	// 		$mimetype = $this->files->fileMimeType($path);

	//     $response = Response::make($file, 200);
	// 	 	$response->header('Content-Type', $mimetype);
	  	
	//   	//if ($request->has('download') && $request->input('download')=='true')
	//   	$response->header('Content-Disposition', 'attachment; filename="'.$p6.'"');

	// 	  return $response;
	// 	} catch (Exception $e) {
	// 		return abort('404');
	// 	}
	// }






	






}