<?php namespace App\Http\Controllers\Uploader;

use App\Events\Upload\ExportRequestSuccess;
use App\Models\Branch;
use File;
use Validator;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Repositories\FileUploadRepository as FileUploadRepo;
use App\Repositories\EmployeeRepository as Employee;
use App\Repositories\EmploymentActivityRepository as EmpActivity; 



class EmployeeTransferController extends Controller {

	protected $files;
	protected $web;
	protected $fileUploadRepo;
	protected $employee;
	protected $empActivity;

	public function __construct() {

		$this->employee = new Employee;

		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
		$this->web = new StorageRepository(new PhpRepository, 'web');
		$this->fileUploadRepo = new FileUploadRepo;

		$this->path['temp'] = strtolower(session('user.branchcode')).DS.now('year').DS;
		$this->path['web'] = config('gi-dtr.upload_path.web').session('user.branchcode').DS.now('year').DS;

	}


	public function processprocessEmployeeTransferRequest(Request $request, EmpActivity $empActivity) {
		
    $this->empActivity = $empActivity;

		$rules = [
			'filetype'  		=> 'required',
			'filename'			=> 'required|exportreq',
			'etrf-filename'	=> 'required',
			'cashier'				=> 'required',
		];

		$msg = [
			'required' => 'Kindly attach the :attribute.',
			'exportreq' => 'Kindly attach the :attribute on 1st attachment.',
		];

		$attrib = [
			'filename' => 'Export Request',
	    	'etrf-filename' => 'Employee Transfer Request Form on 2nd attachment.',
		];
		
		$validator = Validator::make($request->all(), $rules, $msg, $attrib);

		if ($validator->fails())
			return redirect()->back()->withErrors($validator);

		$req = $this->path['temp'].$request->input('filename');
		$etrf = $this->path['temp'].$request->input('etrf-filename');

		if (!$this->web->exists($req) || !$this->web->exists($etrf)) // check if in /public/upload dir?
			return redirect()->back()->withErrors(['error'=>'Something went wrong. Please try again.']);

		$webpath_req = $this->web->realFullPath($req);
		$webpath_etrf = $this->web->realFullPath($etrf);

		if (strtolower(mime_content_type($webpath_req))!=='text/plain')
			return redirect()->back()->withErrors(['error'=>'Invalid Export Request Key format.']);

		if (!in_array(strtolower(mime_content_type($webpath_etrf)), ['image/jpg', 'image/jpeg','image/png','image/gif', 'application/pdf']))
			return redirect()->back()->withErrors(['error'=>'Invalid Employee Transfer Request Form format.']);

		$no = substr($request->input('filename'), 2, 6);

		$content = File::get($webpath_req);
		$x = explode(':', $content);

		$br = strtoupper(session('user.branchcode'));
		$fr = strtoupper(trim($x[2]));
		$to = strtoupper(trim($x[4]));

		if ($br!==$fr)
			return redirect()->back()->withErrors(['error'=>'Invalid Export Request Key branch.']);

		$e = $this->employee
							->skipCriteria()
							->skipCache()
							->findWhere(['code'=>trim($x[5])], ['code', 'lastname', 'firstname', 'middlename', 'branchid', 'id'])
							->first();

		// $ea = $this->empActivity->skipCache()->findWhere(['employee_id'=>$e->id, ['status','<>',10]])->first();
		// if (!is_null($ea)) {
		// 	$ea->load(['branch', 'branchto']);
		// 	return redirect()->back()->withErrors(['error'=>'Pending ongoing '.$ea->type.' request found. '.$ea->branch->code.' -> '.$ea->branchto->code]);
		// }

		if (is_null($e))
			return redirect()->back()->withErrors(['error'=>'Invalid Export Request Key.']);

		$to_br = Branch::select(['code', 'descriptor', 'id'])->where('code', $to)->first();
		if (is_null($to_br))
			return redirect()->back()->withErrors(['error'=>'Invalid Export Request Key branch destination.']);

		$date = c($x[0]);
		$request->merge(['date'=>$date->format('Y-m-d')]);

		$data = [
			'date' 					=> $date->format('Y-m-d'),
			'employee_id' 	=> $e->id,
			'type' 					=> strtoupper(trim($x[1])),
			'branch_id' 		=> session('user.branchid'),
			'to_branch_id' 	=> $to_br->id,
			'effective' 		=> c($x[7])->format('Y-m-d'),
			'passcode'			=> $this->generate_code(),
			'status'				=> 1
		];

		/* transfer REQ file */
		$storage_req 	= 'REQ'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$request->input('filename');
		$file1 = $this->createFileUpload($req, $request, '11E9E1991C1B0D85A7E0F125A67282E0');
		try {
   		$this->files->moveFile($webpath_req, $storage_req, false); // false = override file!
    } catch(Exception $e) {
			return redirect()->back()
			->with('alert-error', 'Error on moving file. '.$e->getMessage())
			->with('alert-important', ' ');
    }
    $data['req_ex'] = $request->input('filename');

		/* transfer ETRF file */
    $ext = strtolower(pathinfo($request->input('etrf-filename'), PATHINFO_EXTENSION));
    $etrf_filename = 'ETRF '.$fr.'-'.$to.' '.$e->code.' '.strtoupper($e->firstname).' '.strtoupper($e->lastname).'.'.$ext;
		$storage_etrf = 'ETRF'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$etrf_filename;
		$request->merge(['filename'=>$request->input('etrf-filename')]);
		$file2 = $this->createFileUpload($etrf, $request, '11E9E19E1C1B0D85A7E03E17EA90908F');
		try {
   		$this->files->moveFile($webpath_etrf, $storage_etrf, false); // false = override file!
    } catch(Exception $er) {
			return redirect()->back()
			->with('alert-error', 'Error on moving file. '.$er->getMessage())
			->with('alert-important', ' ');
    }


    /* make .PAS key */
    $data['pas_key'] = $this->generate_code();

    $exkey = 'EX'.$e->code.'.PAS';
    $storage_exkey  = 'REQ'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$exkey;
    // $webpath_exkey = $this->web->realFullPath($this->path['temp'].$exkey);
    try {
      $cp = File::copy($this->files->realFullPath($storage_req), $this->files->realFullPath($storage_exkey));
    } catch(Exception $er) {
      return redirect()->back()
      ->with('alert-error', 'Error on generating passkey (cp). '.$er->getMessage())
      ->with('alert-important', ' ');
    }
    try {
      $ap = File::append($this->files->realFullPath($storage_exkey), $data['pas_key']);
    } catch(Exception $er) {
      return redirect()->back()
      ->with('alert-error', 'Error on generating passkey (ap). '.$er->getMessage())
      ->with('alert-important', ' ');
    }


    $data['file_type'] = 2; //etrf
    $data['file_path'] = $storage_etrf;
    $data['file_upload_id'] = $file2->id;
    $data['stage']    = 1;
    $data['stage1']   = Carbon::now();

    //$emp_activity = NULL;
    //try {
      $emp_activity = $this->createEmpAtivity($data);
    //} catch (\Exception $ex) {

    //}



    if (!is_null($emp_activity))
    	$this->fileUploadRepo->update(['processed'=>1], $file1->id);

    $data['trail']    = $fr.'-'.$to;
    $data['manno']    = $e->code;
    $data['fullname'] = strtoupper($e->firstname).' '.strtoupper($e->lastname);
    $data['brcode']   = $fr;
    $data['notes']    = empty(trim($request->input('notes'))) ? NULL : trim($request->input('notes'));
    $data['cashier']  = trim($request->input('cashier'));

    // if (app()->environment()==='production')
		event(new ExportRequestSuccess($emp_activity, $data));
		//event($emp_activity, 'empActivity.upload');

    return redirect()
    					->back()
    					->with('alert-success', 'Success on uploading Export Request of '.$e->firstname.' '.$e->lastname)
	    				->with('alert-important', 'success');


		return $content.$this->generate_code();
		return $e->branch->code;
	}


	private function createEmpAtivity($data){

	 	

    return $this->empActivity->create($data)?:NULL;
  }



	private function generate_code() {
		return rand(10000000, 99999999);
	}



  public function getPasskeyDownload(Request $request, $id) {
    return $id;
  }



	/*************  **************/
	private function createFileUpload($src, Request $request, $doctypeid){

  	$d = Carbon::parse($request->input('date').' '.c()->format('H:i:s'));

	 	$data = [
	 		'branch_id' 		=> session('user.branchid'),
    	'filename' 			=> $request->input('filename'),
    	'year' 					=> $d->format('Y'), //$request->input('year'),
    	'month' 				=> $d->format('m'), //$request->input('month'),
    	'size' 					=> $this->web->fileSize($src),
    	'mimetype' 			=> $this->web->fileMimeType($src),
    	'filetype_id'		=> $doctypeid,
    	'terminal' 			=> clientIP(), //$request->ip(),
    	'user_remarks' 	=> $request->input('notes'),
    	'user_id' 			=> $request->user()->id,
    	'cashier' 			=> $request->input('cashier'),
    	'updated_at' 		=> c()
    ];

    return $this->fileUploadRepo->create($data)?:NULL;
  }



}