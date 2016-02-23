<?php namespace App\Http\Controllers;

use File;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Dflydev\ApacheMimeTypes\PhpRepository;
use Illuminate\Filesystem\Filesystem;
use App\Http\Controllers\Controller;
use App\Repositories\PosUploadRepository;
use App\Repositories\StorageRepository;
use App\Repositories\Filters\WithBranch;
use App\Repositories\Filters\ByBranch;
use App\Repositories\Filters\ByUploaddate;
use App\Models\Backup;
use App\Models\DailySales;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as Http404;

class BackupController extends Controller 
{

	protected $files;
	protected $pos;
	protected $fs;
	protected $branch;
	protected $mime;
	protected $backup;
	public $override = false;

	public function __construct(Request $request, PhpRepository $mimeDetect, PosUploadRepository $posuploadrepo){
		$this->branch = session('user.branchcode');
		$this->mime = $mimeDetect;
		$this->fs = new Filesystem;
		$this->files = new StorageRepository($mimeDetect, 'files.'.app()->environment());
		$this->pos = new StorageRepository($mimeDetect, 'pos.'.app()->environment());
		$this->web = new StorageRepository($mimeDetect, 'web');
		$this->backup = $posuploadrepo;
  	$this->backup->pushFilters(new ByBranch($request));
  	
		//$this->backup->pushFilters(new WithBranch(['code', 'descriptor', 'id']));

		
		$this->path['temp'] = strtolower(session('user.branchcode')).DS.now('year').DS;
		$this->path['web'] = config('gi-dtr.upload_path.web').session('user.branchcode').DS.now('year').DS;
	
		
	}

	

	private function setUri($param1=null, $param2=null){
		//$uri = '';
		//$uri .= (is_null($param1) && is_year($param1)) ? $param1 : now('Y');

		if(!is_null($param2) && is_month($param2)){

			if(!is_null($param1) && is_year($param1)){
				$uri = '/'.$param1.'/'.$param2;
			} else {
				throw new Http404("Error Processing Request");
			}
		} else if(!is_null($param1) && is_year($param1)) {
			$uri = '/'.$param1;
		} else if(!is_null($param1) && !is_year($param1)) {
			throw new Http404();
		} else {
			$uri = '';//throw new Http404();
		}
		return $uri;
	}

	public function getIndex(Request $request, $param1=null, $param2=null) {
		$folder = $this->setUri($param1, $param2);
		//return $folder;
		$data = $this->pos->folderInfo($folder);
		//return $data;
		//return dd(count($data['breadcrumbs']));
		return view('backups.filelist')->with('data', $data)->with('tab', 'pos');
	} 
	
	//backups/history
	public function getHistory(Request $request) {

		if($request->input('all')==='1' || $request->user()->username==='cashier') {
			$this->backup->skipFilters();
			$all = true;
		} else 
			$all = false;
		
		
		$this->backup->with(['branch'=>function($query){
        $query->select(['code', 'descriptor', 'id']);
      }])->orderBy('uploaddate', 'DESC')->all();
		
		$backups = $this->backup->paginate(10, $columns = ['*']);

		if($request->input('all')==='1' || $request->user()->username==='cashier') // for Query String for URL
			$backups->appends(['all' => '1']);
		
		return view('backups.index')->with('backups', $backups)->with('all', $all);
	}


	public function getUploadIndex(Request $request) {

		return view('backups.upload');
	}


	private function getStorageType($filename){
		if(strtolower(pathinfo($filename, PATHINFO_EXTENSION))==='zip')
				return $this->pos;
		
		return $this->files;
	}

	private function isBackup(Request $request) {
		return (starts_with($request->input('filename'),'GC') 
					&& strtolower(pathinfo($request->input('filename'), PATHINFO_EXTENSION))==='zip')
					? true : false;
	}

	/* move file from web to maindepot
	*/
	public function putfile(Request $request) {

		$yr 	= empty($request->input('year')) 	? now('Y'):$request->input('year');
		$mon 	= empty($request->input('month')) ? now('M'):$request->input('month');

		$filepath = $this->path['temp'].$request->input('filename');
		$storage_path = strtoupper($this->branch).DS.$yr.DS.$mon.DS.$request->input('filename'); 
		//$storage_path = config('gi-dtr.upload_path.web').session('user.branchcode').DS.now('year').DS.$request->input('filename');



		if($this->web->exists($filepath)){ //public/uploads/{branch_code}/{year}/{filename}.ZIP

			$backup = $this->createPosUpload($filepath, $request);
			
			/*** check if backup file ****/
			if(!$this->isBackup($request)) {
				$msg = $backup->filename.' not backup';
				if(!is_null($backup)){
					$d = $this->web->deleteFile($filepath);
					$msg .= $d ? ' & deleted':'';
					$this->updateBackupRemarks($backup, $msg);
				}
				return redirect('/backups/upload')->with('alert-error', $msg);
			} 
				

			if(!$this->extract($filepath)){
				$msg =  'Unable to extract '. $backup->filename;
				$d = $this->web->deleteFile($filepath);
				$msg .= $d ? ' & deleted':'';
				$this->updateBackupRemarks($backup, $msg);
				$this->removeExtratedDir();
				return redirect('/backups/upload')->with('alert-error', $msg);
			}

			try {
				$this->verifyBackup($request);
			} catch (\Exception $e) {
				$msg =  $e->getMessage();
				$d = $this->web->deleteFile($filepath);
				$msg .= $d ? ' & deleted':'';
				$this->updateBackupRemarks($backup, $msg);
				$this->removeExtratedDir();
				return redirect('/backups/upload')->with('alert-error', $msg);
			}


			if(!$this->processDailySales($backup)){
				$msg = 'File: '.$request->input('filename').' unable to process daily sales!';
				$d = $this->web->deleteFile($filepath);
				$msg .= $d ? ' & deleted':'';
				$this->updateBackupRemarks($backup, $msg);
				$this->removeExtratedDir();
				return redirect('/backups/upload')->with('alert-error', $msg);
			}

			try {
	     	$this->pos->moveFile($this->web->realFullPath($filepath), $storage_path, true); // false = override file!
	    }catch(\Exception $e){
					return redirect('/backups/upload')->with('alert-error', $e->getMessage());
	    }
	     
			$this->removeExtratedDir();
			return redirect('/backups/upload')->with('alert-success', $backup->filename.' saved and processed daily sales!');
			





			$storage = $this->getStorageType($filepath); // check if ZIP or Document File (e.g JPG, PNG) 
																									 // & return which StorageRepository ($this->pos or this->file)
			


			

	    /*** if backup file ****/
	    if(starts_with($storage->getType(),'pos') && starts_with($request->input('filename'),'GC')) {

		    $res = $this->createPosUpload($storage_path, $request);
		    if(!$res)
					return redirect('/backups/upload')
									->with('alert-error', 'File: '.$request->input('filename').' unable to create record');
		    
				if($this->extract($storage_path)) {

					try {
						$this->verifyBackup($request);
					} catch (\Exception $e) {
						$this->removeExtratedDir();
						return redirect('/backups/upload')->with('alert-error', $e->getMessage());
					}

					if(!$this->processDailySales($res))
						return redirect('/backups/upload')
										->with('alert-error', 'File: '.$request->input('filename').' unable to extract');
					
					$this->removeExtratedDir();
					return redirect('/backups/upload')->with('alert-success', 'File: '.$request->input('filename').' successfully uploaded and processed daily sales!');
				} else {
					return redirect('/backups/upload')->with('alert-error', 'Unable to extract backup.');
				}


	    } else {
				return redirect('/backups/upload')->with('alert-success', 'File: '.$request->input('filename').' successfully uploaded!');
	    }
			
		
		} else {
			$this->logAction('move:error', 'user:'.$request->user()->username.' '.$request->input('filename').' message:try_again');
			return redirect('/backups/upload')->with('alert-error', 'File: '.$request->input('filename').' do not exist! Try to upload again..');
		}
	} 


	private function logAction($action, $log) {
		$logfile = base_path().DS.'logs'.DS.now().'-log.txt';
		$new = file_exists($logfile) ? false : true;
		if($new){
			$handle = fopen($logfile, 'w+');
			chmod($logfile, 0775);
		} else
			$handle = fopen($logfile, 'a');

		$ip = $_SERVER['REMOTE_ADDR'];
		$brw = $_SERVER['HTTP_USER_AGENT'];
		$content = date('r')." | {$ip} | {$action} | {$log} \t {$brw}\n";
    fwrite($handle, $content);
    fclose($handle);
	}	

 	// depricated
	public function setPath($filename){
		if(strtolower(pathinfo($filename, PATHINFO_EXTENSION))==='zip')
				return 'pos'.DS.$this->path['temp'].DS;
		else
				return 'files'.DS.$this->path['temp'].DS;
	}

	/* upload to web from ajax 
	*/
	public function postfile(Request $request) {

		
		if($request->file('pic')->isValid()) {

			$filename = rawurldecode($request->file('pic')->getClientOriginalName());
			
			//$ext = $request->file('pic')->guessExtension();
			//$mimetype = $request->file('pic')->getClientMimeType();
			
			$file = File::get($request->file('pic'));

			$path = $this->path['temp'].$filename;

			$res = $this->web->saveFile($path, $file, false); // false = override file!


			if($res===true){
				return json_encode(['status'=>'success', 
													'code'=>'200', 
													'message'=>$res, 
													'year'=>$request->input('year'),
													'month'=>$request->input('month')]);
			} else {
				return json_encode(['status'=>'warning', 
													'code'=>'201', 
													'message'=>$res, 
													'year'=>$request->input('year'),
													'month'=>$request->input('month')]);
			}
			

		} else {
			return redirect('/upload/backup')
								->with('alert-error', 'File: '.$request->input('filename').' corrupted! Try to upload again..');
		}
		
	}



	// for /put/upload/postfile @ $this->putfile()
  //public function processPosBackup($src, $ip){
  public function createPosUpload($src, Request $request){

	 	$data = [
	 		'branchid' => session('user.branchid'),
    	'filename' => $request->input('filename'),
    	'year' => $request->input('year'),
    	'month' => $request->input('month'),
    	'size' => $this->web->fileSize($src),
    	'mimetype' => $this->web->fileMimeType($src),
    	'terminal' => clientIP(), //$request->ip(),
    	'lat' => round($request->input('lat'),7), 
    	'long' => round($request->input('lng'),7), 
    	'remarks' => $src.':'.$request->input('notes'),
    	'userid' => $request->user()->id
    ];

    return $this->backup->create($data)?:NULL;
  }

  public function extract_old($src, $pwd=NULL){
  	return $this->backup->extract($src, $pwd);
  }

  public function ds(Request $request) {
  	$this->backup->ds->pushFilters(new WithBranch(['code', 'descriptor', 'id']));
  	return $this->backup->ds->lastRecord();
  }

  public function extract($filepath) {
  	return $this->backup->extract($filepath, 'admate');	
  }

  public function verifyBackup(Request $request) {
  	try {
  		$code = $this->backup->getBackupCode(); 
  	} catch (\Exception $e) {
  		throw new \Exception($e->getMessage());
  	}
  	
  	if(strtolower($code)===strtolower($request->user()->branch->code)) {
  		return $code;
  	} else {
  		throw new \Exception("Backup file is property of ". $code .' not '.$request->user()->branch->code);
  	}
  }

  public function processDailySales(Backup $posupload){
  	//$this->backup->extract($filepath, 'admate');
  	$res = $this->backup->postDailySales();
  	if($res) 
  		$this->backup->update(['processed'=>1], $posupload->id);
  	
  	return $res;
  }

  public function removeExtratedDir() {
  	return $this->backup->removeExtratedDir();
  }

  public function updateBackupRemarks(Backup $posupload, $message) {
  	$x = explode(':', $posupload->remarks);
		$msg = empty($x['1']) 
			? $posupload->remarks.' '. $message
			: $posupload->remarks.', '. $message;
					
		return $this->backup->update(['remarks'=> $msg], $posupload->id);
  }





  public function getDownload(Request $request, $p1=NULL, $p2=NULL, $p3=NULL, $p4=NULL, $p5=NULL){
    
    if(is_null($p2) || is_null($p2) || is_null($p3) || is_null($p4) || is_null($p5)){
    	throw new Http404("Error Processing Request");
    }

    $path = $p2.'/'.$p3.'/'.$p4.'/'.$p5;

		$storage = $this->getStorageType($path);

		$file = $storage->get($path);
		$mimetype = $storage->fileMimeType($path);

    $response = \Response::make($file, 200);
	 	$response->header('Content-Type', $mimetype);
  	$response->header('Content-Disposition', 'attachment; filename="'.$p5.'"');

	  return $response;
  }
}