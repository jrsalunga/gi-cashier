<?php namespace App\Http\Controllers;
use DB;
use ZipArchive;
use Validator;
use Exception;
use Carbon\Carbon;
use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use App\Http\Controllers\Controller;
use App\Repositories\StorageRepository;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use App\Repositories\FileUploadRepository as FileUploadRepo;
use App\Repositories\DepslipRepository as DepslipRepo;
use Dflydev\ApacheMimeTypes\PhpRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as Http404;
use Vinkla\Pusher\Facades\Pusher;
use Vinkla\Pusher\PusherManager;
use App\Events\Backup\ProcessSuccess;
use Mail;

class UploaderController extends Controller 
{
	protected $posUploadRepo;
	protected $fileUploadRepo;
	protected $depslip;
	protected $files;
	protected $pos;
	protected $web;
	protected $backupCtrl;

	public function __construct(PosUploadRepo $posUploadRepo, FileUploadRepo $fileUploadRepo, DepslipRepo $depslip) {
		$this->posUploadRepo = $posUploadRepo;
		$this->fileUploadRepo = $fileUploadRepo;
		$this->depslip = $depslip;
		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
		$this->pos = new StorageRepository(new PhpRepository, 'pos.'.app()->environment());
		$this->web = new StorageRepository(new PhpRepository, 'web');

		$this->path['temp'] = strtolower(session('user.branchcode')).DS.now('year').DS;
		$this->path['web'] = config('gi-dtr.upload_path.web').session('user.branchcode').DS.now('year').DS;
	}



	public function getIndex(Request $request) {  
		return view('uploader.index');
	}

	public function getBackupIndex(Request $request) {  
		return view('uploader.backup');
	}

	



	public function putFile(Request $request) {  
		if ($request->input('filetype')=='backup')
			return $this->processBackup($request);
		else if ($request->input('filetype')=='depslp')
			return $this->processBankSlip($request);
		else
			return $this->processUnknownFile($request);
	}

	private function processBackup(Request $request) {

		$rules = [
			'filename'		=> 'required',
			'filetype'  	=> 'required',
			'backup_type'	=> 'required',
			'cashier'			=> 'required',
		];

		$messages = [];
		
		$validator = Validator::make($request->all(), $rules, $messages);

		if ($validator->fails())
			return redirect()->back()->withErrors($validator);

		$backup_date = $this->backupParseDate($request);
		$filepath = $this->path['temp'].$request->input('filename');

		if ($backup_date) { // check if filename (GC040616.ZIP) is valid date 

			$mon = $backup_date->format('m');
			$yr = $backup_date->format('Y');
			$storage_path = strtoupper(session('user.branchcode')).DS.$yr.DS.$mon.DS.$request->input('filename'); 

			if ($this->web->exists($filepath)) { //public/uploads/{branch_code}/{year}/{filename}.ZIP

				$backup = $this->createPosUpload($filepath, $request);
				$backup->date = $backup_date;

				// check file type 
				if ($request->input('backup_type')==='payroll') {

					try {
						$this->emailToHRD($backup, $filepath);
			    } catch (Exception $e) {
						return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
			    }
			    $this->updateBackupRemarks($backup, 'Payroll backup and emailed to HR');
			    $this->web->deleteFile($filepath);
			    $this->processed($backup);
					return redirect('/uploader?success='.strtolower(session('user.branchcode')).'-'.strtolower($backup->cashier))->with('alert-success', 'Payroll backup file: '.$backup->filename.' has been saved on server!');
				
				} else {

					DB::beginTransaction();

					if (!$this->extract($filepath)) {
						$msg =  'Unable to extract '. $backup->filename.' but the backup file is already on server.';
						$this->removeExtratedDir();
						DB::rollBack();
						$this->updateBackupRemarks($backup, $msg);

						try {
				     	$this->pos->moveFile($this->web->realFullPath($filepath), '..'.DS.'BACKUP_PROCESSING_ERROR'.DS.$storage_path, false); // false = override file!
				    } catch (Exception $e) {
							return redirect()->back()->withErrors(['error'=>'Error on moving file.']);
				    }
						return redirect()->back()->with('alert-success', $msg)->with('alert-important', '');
					}

					




					try {
			     	$this->pos->moveFile($this->web->realFullPath($filepath), $storage_path, false); // false = override file!
			    } catch(Exception $e) {
						return redirect()->back()->withErrors(['error'=>'Error on moving file.']);
			    }

					DB::commit();
					$this->processed($backup);
					$this->removeExtratedDir();
					return redirect('/uploader?success='.strtolower(session('user.branchcode')).'-'.strtolower($backup->cashier))->with('alert-success', $backup->filename.' saved on server!');
				
				}

			}
			return redirect()->back()->withErrors(['error'=>'File: '.$request->input('filename').' do not exist! Try to upload again..']);
		} 
		$this->web->deleteFile($filepath); // delete the uploaded file
		return redirect()->back()->withErrors(['error'=>'File '.$request->input('filename').' invalid backup file']);
	}



	/************* for processBackup **************/

	private function emailToHRD($backup, $filepath) {

		if( c('2016-11-27')->lte($backup->date) ) {

			$z = new ZipArchive();
			$zip_status = $z->open('uploads'.DS.$filepath);

			if($zip_status === true) {
				
				$z->setPassword('admate');
				$z->deleteName('OR'.$backup->date->format('Ym').'.ZIP');

				$data = [
					'branchcode' 	=> session('user.branchcode'),
					'attachment' 	=> $z->filename,
					'numfiles'		=> $z->numFiles,
					'user'				=> session('user.fullname'),
					'cashier'			=> $backup->cashier,
					'filename'		=> $backup->filename
				];

				$z->close();

				Mail::send('emails.email_to_hrd', $data, function ($message) use ($data) {
		        $message->subject($data['branchcode'].' '.$data['filename'].' PAYROLL BACKUP [payroll]');
		        $message->from('no-reply@giligansrestaurant.com', 'GI App - '.$data['branchcode'].' Cashier');
		        $message->to('gi.efiles@gmail.com');
		        //$message->to('freakyash02@gmail.com');
		       	$message->attach($data['attachment']);
		    });

				return true;
			} else
				throw new Exception('Error: Zip status error', 1);
				
		}
	}

	
	private function backupParseDate(Request $request) {

		$f = pathinfo($request->input('filename'), PATHINFO_FILENAME);

		$m = substr($f, 2, 2);
		$d = substr($f, 4, 2);
		$y = '20'.substr($f, 6, 2);
		
		if(is_iso_date($y.'-'.$m.'-'.$d))
			return carbonCheckorNow($y.'-'.$m.'-'.$d);
		else 
			return false;
	}

	private function extract($filepath) {
  	return $this->posUploadRepo->extract($filepath, 'admate');	
  }

  private function removeExtratedDir() {
  	return $this->posUploadRepo->removeExtratedDir();
  }

  private function removeFile($path) {
  	return $this->posUploadRepo->removeFile($path);
  }

  private function updateBackupRemarks(Backup $posupload, $message) {
  	$x = explode(':', $posupload->remarks);
		$msg = empty($x['1']) 
			? $posupload->remarks.' '. $message
			: $posupload->remarks.', '. $message;
					
		return $this->posUploadRepo->update(['remarks'=> $msg], $posupload->id);
  }

  private function createPosUpload($src, Request $request){

  	$d = $this->backupParseDate($request);

	 	$data = [
	 		'branchid' 	=> session('user.branchid'),
    	'filename' 	=> $request->input('filename'),
    	'year' 			=> $d->format('Y'), //$request->input('year'),
    	'month' 		=> $d->format('m'), //$request->input('month'),
    	'size' 			=> $this->web->fileSize($src),
    	'mimetype' 	=> $this->web->fileMimeType($src),
    	'terminal' 	=> clientIP(), //$request->ip(),
    	'lat' 			=> round($request->input('lat'),7), 
    	'long' 			=> round($request->input('lng'),7), 
    	'remarks' 	=> $request->input('notes'),
    	'userid' 		=> $request->user()->id,
    	'filedate' 	=> $d->format('Y-m-d').' '.Carbon::now()->format('H:i:s'),
    	//'filedate' => $d->format('Y-m-d').' 06:00:00',
    	'cashier' 	=> $request->input('cashier')
    ];

    return $this->posUploadRepo->create($data)?:NULL;
  }

  private function processed(Backup $posupload){
  	return $this->posUploadRepo->update(['processed'=>1], $posupload->id);
  }


	/************* end: processBackup **************/


	public function processBankSlip(Request $request) {

		$rules = [
			'filename'		=> 'required',
			'filetype'  	=> 'required',
			'date'				=> 'required|date',
			'amount'			=> 'required|integer',
			'cashier'			=> 'required',
		];

		$messages = [];
		
		$validator = Validator::make($request->all(), $rules, $messages);

		if ($validator->fails()) {
			$this->web->deleteFile($this->path['temp'].$request->input('filename'));
			return redirect()->back()->withErrors($validator);
		}

		$ext = strtolower(pathinfo($request->input('filename'), PATHINFO_EXTENSION));
		if (!in_array(strtolower($ext), ['png', 'jpeg', 'jpg', 'pdf'])) {
			$this->web->deleteFile($this->path['temp'].$request->input('filename'));
			return redirect()->back()->withErrors(['error'=>$request->input('filename').': invalid file extension for Bank Deposit Slip.']);
		}

		$date = carbonCheckorNow($request->date);
		$upload_path = $this->path['temp'].$request->input('filename');

		if ($this->web->exists($upload_path)) { //public/uploads/{branch_code}/{year}/{file}
			$br = strtoupper(session('user.branchcode'));

			$filename = strtoupper($request->input('filename'));
			if (!starts_with(strtoupper($filename), 'DEPSLP '.$br))
				$filename = 'DEPSLP_'.$br.'_'.$date->format('Ymd').'.'.$ext;
			
			$storage_path = 'DEPSLP'.DS.$br.DS.$date->format('Y').DS.$date->format('m').DS.$filename; 

			$file = $this->createFileUpload($upload_path, $request);

			//return $this->files->realFullPath($storage_path);
			//return dd($this->files->exists($this->files->realFullPath($storage_path)));

			try {
	     	$this->files->moveFile($this->web->realFullPath($upload_path), $storage_path, true); // false = override file!
	    } catch(Exception $e) {
				return redirect()->back()
				//->withErrors(['error'=>'Error on moving file. '.$e->getMessage()])
				->with('alert-error', 'Error on moving file. '.$e->getMessage())
				->with('alert-important', ' ');
	    }

	    if (!is_null($this->createDepslip($file, $filename)))
	    	$this->fileUploadRepo->update(['processed'=>1], $file->id);

	    return redirect('/uploader?success='.strtolower($br).'-'.strtolower($request->cashier))
	    ->with('alert-success', $request->filename.' saved on server as '.$filename.'.')
	    ->with('alert-important', '');

		}
		return redirect()->back()->withErrors(['error'=>'File: '.$request->input('filename').' do not exist! Try to upload again..']);
	}

	/************* for processBankSlip **************/

	private function createFileUpload($src, Request $request){

  	$d = Carbon::parse($request->input('date').' '.c()->format('H:i:s'));

	 	$data = [
	 		'branch_id' 		=> session('user.branchid'),
    	'filename' 			=> $request->input('filename'),
    	'year' 					=> $d->format('Y'), //$request->input('year'),
    	'month' 				=> $d->format('m'), //$request->input('month'),
    	'size' 					=> $this->web->fileSize($src),
    	'mimetype' 			=> $this->web->fileMimeType($src),
    	'filetype_id'	=> 'C1CCBE28CCDA11E6A3D000FF18C615EC',
    	'terminal' 			=> clientIP(), //$request->ip(),
    	'user_remarks' 	=> $request->input('notes'),
    	'user_id' 			=> $request->user()->id,
    	'cashier' 			=> $request->input('cashier')
    ];

    return $this->fileUploadRepo->create($data)?:NULL;
  }

	private function createDepslip($file, $filename){

	 	$data = [
	 		'branch_id' 			=> session('user.branchid'),
    	'filename' 				=> $filename,
    	'date' 						=> request()->input('date'),
    	'amount' 					=> request()->input('amount'),
    	'file_upload_id' 	=> $file->id,
    	'terminal' 				=> clientIP(), //$request->ip(),
    	'remarks' 				=> request()->input('notes'),
    	'user_id' 				=> request()->user()->id,
    	'cashier' 				=> request()->input('cashier')
    ];

    return $this->depslip->create($data)?:NULL;
  }


	/************* end: processBankSlip **************/

	public function processUnknownFile(Request $request) {

		$filepath = $this->path['temp'].$request->input('filename');
		$br = strtoupper(session('user.branchcode'));
		$storage_path = $br.DS.c()->format('Y').DS.c()->format('m').DS.$request->input('filename'); 
		try {
     	$this->pos->moveFile($this->web->realFullPath($filepath), '..'.DS.'UNKNOWN_BACKUP_FILES'.DS.$storage_path, false); // false = override file!
    } catch (Exception $e) {
			return redirect()->back()->withErrors(['error'=>'Error on moving file.'.$e->getMessage()]);
    }
		return redirect()->back()->withErrors(['error'=>'Uploaded. Unknown file type.']);
	}



}