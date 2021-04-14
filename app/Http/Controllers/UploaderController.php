<?php namespace App\Http\Controllers;
use DB;
use Mail;
use Event;
use Validator;
use Exception;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Backup;
use Mike42\Escpos\Printer;
use Illuminate\Http\Request;
use App\Repositories\Rmis\Invdtl;
use App\Repositories\Rmis\Invhdr;
use App\Repositories\Rmis\Orpaydtl;
use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;
use App\Events\Backup\ProcessSuccess;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Events\Upload\Depslp as DepslpUpload;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\SetslpRepository as SetslpRepo;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use App\Repositories\DepslipRepository as DepslipRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use App\Repositories\FileUploadRepository as FileUploadRepo;
use App\Repositories\EmploymentActivityRepository as EmpActivity; 
use App\Repositories\CashAuditRepository as CashAudit; 
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as Http404;
use App\Repositories\SupplierRepository as Supplier;
use App\Repositories\DoctypeRepository as Doctype;


class UploaderController extends Controller 
{
	protected $posUploadRepo;
	protected $fileUploadRepo;
	protected $depslip;
	protected $setslp;
	protected $files;
	protected $pos;
	protected $payroll;
	protected $web;
	protected $backupCtrl;
	protected $ds;
	protected $invdtl;
	protected $orpaydtl;
	protected $invhdr;
  protected $cashAudit;
  protected $supplier;
  protected $doctype;

	public function __construct(PosUploadRepo $posUploadRepo, FileUploadRepo $fileUploadRepo, DepslipRepo $depslip, SetslpRepo $setslp, DSRepo $ds, Invdtl $invdtl, Orpaydtl $orpaydtl, Invhdr $invhdr, CashAudit $cashAudit, Supplier $supplier, Doctype $doctype) {
		$this->posUploadRepo = $posUploadRepo;
		$this->fileUploadRepo = $fileUploadRepo;
		$this->depslip = $depslip;
		$this->setslp = $setslp;
    $this->supplier = $supplier;
    $this->doctype = $doctype;
		$this->ds = $ds;
		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
		$this->pos = new StorageRepository(new PhpRepository, 'pos.'.app()->environment());
		$this->payroll = new StorageRepository(new PhpRepository, 'payroll.'.app()->environment());
		$this->web = new StorageRepository(new PhpRepository, 'web');

		$this->path['temp'] = strtolower(session('user.branchcode')).DS.now('year').DS;
		$this->path['web'] = config('gi-dtr.upload_path.web').session('user.branchcode').DS.now('year').DS;
	
		$this->invdtl = $invdtl;
		$this->orpaydtl = $orpaydtl;
		$this->invhdr = $invhdr;
    $this->cashAudit = $cashAudit;
	}

	public function getIndex(Request $request) { 
    return view('uploader.index');
    // $n = new \App\Helpers\BossBranch;

    // $e['mailing_list'] = [];

    // // $e['mailing_list'] = [
    // //     ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
    // //     ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
    // //   ];

    // // return dd($e['mailing_list']);

    // foreach ($n->getUsers() as $k => $u) {
    //   $e['mailing_list'][$k]['name'] = $u->name;
    //   $e['mailing_list'][$k]['email'] = $u->email;
    // }

    // foreach ($e['mailing_list'] as $p)
    //   echo $p['email'];

    // return;

    // return dd($e['mailing_list']);

    $doctypes = $this->doctype->findWhere(['assigned'=>1]);
    $suppliers = $this->supplier->findWhere(['status'=>1]);


		return view('uploader.index', compact('suppliers', 'doctypes'));
	}

	public function getBackupIndex(Request $request) {  
		return view('uploader.backup');
	}

	public function putFile(Request $request, EmpActivity $empActivity) {  
		if ($request->input('filetype')=='backup')
			return $this->processBackup($request);
		else if ($request->input('filetype')=='depslp')
			return $this->processBankSlip($request);
		else if ($request->input('filetype')=='setslp')
			return $this->processCreditCardSlip($request);
		else if ($request->input('filetype')=='exportreq-etrf')
			return $this->processEmployeeTransferRequest($request, $empActivity);
    else if ($request->input('filetype')=='ap')
      return $this->processAccountsPayablesRequest($request, $empActivity);
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
				if ($request->input('backup_type')==='hr') {

  				$this->posUploadRepo->update(['long'=>1], $backup->id);
					try {
						$this->emailToHRD($backup, $filepath);
			    } catch (Exception $e) {
			    	$this->web->deleteFile($filepath);
						return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
			    }
			    //$this->updateBackupRemarks($backup, 'Payroll backup and emailed to HR');
  				
  				$this->posUploadRepo->update(['processed'=>2], $backup->id);
			    $this->web->deleteFile($filepath);
			    return redirect()
		    					->route('uploader', ['brcode'=>strtolower(session('user.branchcode')),'u'=>strtolower($backup->cashier),'type'=>'hr'])
		    					->with('hr.success', $backup->filename);

		    } elseif ($request->input('backup_type')==='payroll') {

  				$this->posUploadRepo->update(['long'=>2], $backup->id);
		    	if (!is_payroll_backup($request->input('filename'))) {
			    	$this->web->deleteFile($filepath);
		    		return redirect()->back()
		    							->with('alert-error', $request->input('filename'). ' is not a GI PAY Payroll Backup. example: PR031517.ZIP')
		    							->with('alert-important', '');
		    	}

		    	try {
						$this->emailPayrollBackup($backup, $storage_path);
			    } catch (Exception $e) {
			    	$this->web->deleteFile($filepath);
						return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
			    }

			    try {
			     	$this->payroll->moveFile($this->web->realFullPath($filepath), $storage_path, false); // false = override file!
			    } catch(Exception $e) {
			    	$this->web->deleteFile($filepath);
						return redirect()->back()->withErrors(['error'=>'Error on moving file.']);
			    }

			    if (app()->environment()==='production')
						event(new ProcessSuccess($backup, $request->user()));
  				
  				$this->posUploadRepo->update(['processed'=>1], $backup->id);
			    $this->web->deleteFile($filepath);
			    return redirect()
		    					->route('uploader', ['brcode'=>strtolower(session('user.branchcode')),'u'=>strtolower($backup->cashier),'type'=>'payroll'])
		    					->with('payroll.success', $backup->filename);


				} elseif ($request->input('backup_type')==='pos') {

					if (!is_pos_backup($request->input('filename')))
		    		return redirect()->back()->withErrors(['error'=>$request->input('filename'). ' is not a POS Backup.']);

					DB::beginTransaction();

					// extract backup
					//$this->logAction('start:extract:backup', '');
					if (!$this->extract($filepath)) {
						$msg =  'Unable to extract '. $backup->filename;
						$res = $this->movedErrorProcessing($filepath, $storage_path);
						$this->updateBackupRemarks($backup, $msg);
						//$this->logAction('error:extract:backup', '');
						$msg .= ', the backup maybe corrupted. Try to generate another backup file and try to re-upload.';
						if($res !== true)
							return $res;
						return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
					}

					$file = $this->createFileUpload($filepath, $request, '87ADF8F1CCDA11E6A3D000FF18C615EC');

					
					if($backup_date->gt(Carbon::parse('2012-12-31'))) { // dont verify branchco
						try {
							$this->verifyBackup($request);
						} catch (Exception $e) {
							$msg =  $e->getMessage();
							
							//$res = $this->movedErrorProcessing($filepath, $storage_path);
							$d = $this->web->deleteFile($filepath);
							$msg .= $d ? ' & deleted':'';
							$this->removeExtratedDir();
							DB::rollBack();

							$this->updateBackupRemarks($backup, $msg);
							//$this->logAction('error:verify:backup', $log_msg.$msg);
							return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
						}
						//$this->logAction('success:verify:backup', $log_msg.$msg);
					}

						
					/******** para maka send kahit hindi EoD ung backup **/
					
					if ( c()->format('Ymd')!=c()->firstOfMonth()->format('Ymd')
						|| (request()->has('_eod') && request()->input('_eod')=='false')
					) {
						try {
							$this->isEoD($backup);
						} catch (Exception $e) {
							$msg =  $e->getMessage();
							//$res = $this->movedErrorProcessing($filepath, $storage_path);							
							$this->removeExtratedDir();
							DB::rollBack();

							$this->updateBackupRemarks($backup, $msg);
							//$this->logAction('error:verify:backup', $log_msg.$msg);
							return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
						}
					}
					



					/******* extract trasanctions data ***********/


					if(!$this->processDailySales($backup)){
						$msg = 'File: '.$request->input('filename').' unable to process daily sales!';
						$res = $this->movedErrorProcessing($filepath, $storage_path);
						$this->updateBackupRemarks($backup, $msg);
						return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
					} else {
						// change location below to recompte when all is ok
						//event(new \App\Events\Backup\DailySalesSuccess($backup)); // recompute Monthlysales
					}


					try {
						$this->processSalesmtd($backup->date, $backup);
					} catch (Exception $e) {
            if (strpos($e->getMessage(), 'timeout')==false)
  						$msg = 'Process Salesmtd: '.$e->getMessage();
            else 
              $msg = 'Error: recent upload still on process, re-upload after 10-30 minutes.';
						$res = $this->movedErrorProcessing($filepath, $storage_path);
						$this->updateBackupRemarks($backup, $msg);
						return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
					} finally {
						event(new \App\Events\Posting\SalesmtdSuccess($backup));
					}
				
				
					try {
						$this->processCharges($backup->date, $backup);
					} catch (Exception $e) {
            if (strpos($e->getMessage(), 'timeout')==false)
  						$msg =  'Process Charges: '.$e->getMessage();
            else 
              $msg = 'Error: recent upload still on process, re-upload after 10-30 minutes.';
						$res = $this->movedErrorProcessing($filepath, $storage_path);
						$this->updateBackupRemarks($backup, $msg);
						//$this->logAction('error:process:charges', $log_msg.$msg);
						return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
					}
					//$this->logAction('success:process:charges', $log_msg.$msg);

					try {
						$this->processPurchased($backup->date);
					} catch (Exception $e) {
            if (strpos($e->getMessage(), 'timeout')==false)
  						$msg =  'Process Purchased: '.$e->getMessage();
            else 
              $msg = 'Error: recent upload still on process, re-upload after 10-30 minutes.';
						//$res = $this->movedErrorProcessing($filepath, $storage_path);
						$this->updateBackupRemarks($backup, $msg);
						//$this->logAction('error:process:purchased', $log_msg.$msg);
						return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
					}


					try {
						$this->processTransfer($backup->branchid, $backup->date);
					} catch (Exception $e) {
            if (strpos($e->getMessage(), 'timeout')==false)
  						$msg =  'Process Transfer: '.$e->getMessage();
            else 
              $msg = 'Error: recent upload still on process, re-upload after 10-30 minutes.';
						//$res = $this->movedErrorProcessing($filepath, $storage_path);
						$this->updateBackupRemarks($backup, $msg);
						//$this->logAction('error:process:purchased', $log_msg.$msg);
						return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
					}

					// push emp meal on purchase
					event('transfer.empmeal', ['data'=>['branch_id'=> $backup->branchid, 'date'=>$backup->date, 'suppliercode'=>session('user.branchcode')]]);
					//$this->logAction('success:process:purchased', $log_msg.$msg);

          // compute delivery fee (GrabFood, Food Panda)   \\App\Listeners\BackupEventListener
          event('deliveryfee', ['data'=>['branch_id'=> $backup->branchid, 'date'=>$backup->date]]);

					

					// added 2017-06-09 to backlog DS trans_cnt, man_pay, man_hrs
					// one time transaction
					if($backup_date->eq(Carbon::parse('2017-06-09'))) { 
						try {
							$this->backlogTransCount($backup->date, $backup);
						} catch (Exception $e) {
							$msg =  $e->getMessage();
							//$res = $this->movedErrorProcessing($filepath, $storage_path);
							$this->updateBackupRemarks($backup, $msg);
						}
					}

					// added 2017-08-22 to backlog DS depo_cash, depo_check
					// one time transaction
					if($backup_date->eq(Carbon::parse('2017-08-22'))) { 
						try {
							$this->backlogDeposits($backup->date, $backup);
						} catch (Exception $e) {
							$msg =  $e->getMessage();
							//$res = $this->movedErrorProcessing($filepath, $storage_path);
							$this->updateBackupRemarks($backup, $msg);
						}
					}

          /*
          try {
            $this->backlogSalesmtdChangeItem($backup->branchid, $backup->date, $backup->date);
          } catch (Exception $e) {
            $msg =  'Process Change Item '.$e->getMessage();
            //$res = $this->movedErrorProcessing($filepath, $storage_path);
            $this->updateBackupRemarks($backup, $msg);
            //$this->logAction('error:process:purchased', $log_msg.$msg);
            return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
          }
          */

          try {
            $this->processCashAudit2($backup->branchid, $backup->date);
          } catch (Exception $e) {
            $msg =  'Process Cash Audit 2: '.$e->getMessage();
            //$res = $this->movedErrorProcessing($filepath, $storage_path);
            $this->updateBackupRemarks($backup, $msg);
            //$this->logAction('error:process:purchased', $log_msg.$msg);
            return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
          }

          $kl = 0;
          try {
            $kl = $this->processKitlog($backup->branchid, $backup->date);
          } catch (Exception $e) {
            if (strpos($e->getMessage(), 'timeout')==false)
              $msg =  'Process Kitlog: '.$e->getMessage();
            else 
              $msg = 'Error: recent upload still on process, re-upload after 10-30 minutes.';
            //$res = $this->movedErrorProcessing($filepath, $storage_path);
            $this->updateBackupRemarks($backup, $msg);
            //$this->logAction('error:process:purchased', $log_msg.$msg);
            return redirect()->back()->with('alert-error', $msg)->with('alert-important', '');
          }

          event(new \App\Events\Process\AggregateComponentDaily($backup->date, $backup->branchid)); // recompute Daily Component
          event(new \App\Events\Process\AggregateDailyExpense($backup->date, $backup->branchid)); // recompute Daily Expense
          event(new \App\Events\Process\AggregatorDaily('trans-expense', $backup->date, $backup->branchid)); // recompute Daily Transfered and update day_expense
          event(new \App\Events\Process\AggregatorDaily('prodcat', $backup->date, $backup->branchid)); 
          
          event(new \App\Events\Process\AggregatorDaily('change_item', $backup->date, $backup->branchid)); // update ds
          event(new \App\Events\Backup\DailySalesSuccess($backup)); // recompute Monthlysales based on DS
					event(new \App\Events\Process\AggregateComponentMonthly($backup->date, $backup->branchid)); // recompute Monthly Component
					event(new \App\Events\Process\AggregateMonthlyExpense($backup->date, $backup->branchid)); // recompute Monthly Expense
					event(new \App\Events\Process\AggregatorMonthly('trans-expense', $backup->date, $backup->branchid));
					event(new \App\Events\Process\AggregatorMonthly('product', $backup->date, $backup->branchid)); // recompute Monthly Expense
					event(new \App\Events\Process\AggregatorMonthly('prodcat', $backup->date, $backup->branchid)); 
					event(new \App\Events\Process\AggregatorMonthly('groupies', $backup->date, $backup->branchid));
          event(new \App\Events\Process\AggregatorMonthly('change_item', $backup->date, $backup->branchid));
          event(new \App\Events\Process\AggregatorMonthly('cash_audit', $backup->date, $backup->branchid));
					event(new \App\Events\Process\RankMonthlyProduct($backup->date, $backup->branchid));

          event(new \App\Events\Process\AggregatorMonthly('charge-type', $backup->date, $backup->branchid));
          event(new \App\Events\Process\AggregatorMonthly('sale-type', $backup->date, $backup->branchid));
          event(new \App\Events\Process\AggregatorMonthly('card-type', $backup->date, $backup->branchid));




          $u = [];
          if ($kl>0) {
            event(new \App\Events\Process\AggregatorKitlog('day_kitlog_food', $backup->date, $backup->branchid));
            event(new \App\Events\Process\AggregatorKitlog('day_kitlog_area', $backup->date, $backup->branchid));
            event(new \App\Events\Process\AggregatorKitlog('month_kitlog_food', $backup->date, $backup->branchid));
            event(new \App\Events\Process\AggregatorKitlog('month_kitlog_area', $backup->date, $backup->branchid));
            event(new \App\Events\Process\AggregatorKitlog('dataset_area', $backup->date, $backup->branchid));
            event(new \App\Events\Process\AggregatorKitlog('dataset_food', $backup->date, $backup->branchid));
            event(new \App\Events\Process\AggregatorKitlog('dataset_area', $backup->date, NULL));
            event(new \App\Events\Process\AggregatorKitlog('dataset_food', $backup->date, NULL));
            $u['kitlog'] = 1;
          }

					/******* end: extract trasanctions data ***********/

					try {
			     	$this->pos->moveFile($this->web->realFullPath($filepath), $storage_path, false); // false = override file!
			    } catch(Exception $e) {
						return redirect()->back()->withErrors(['error'=>'Error on moving file.']);
			    }

					DB::commit();
          $u['lat'] = 1;
					$this->posUploadRepo->update($u, $backup->id);
					$this->fileUploadRepo->update(['processed'=>1], $file->id);
					$this->processed($backup);
					$this->removeExtratedDir();

					if (app()->environment()==='production')
						event(new ProcessSuccess($backup, $request->user()));

					return redirect()
		    					//->route('uploader', ['brcode'=>strtolower(session('user.branchcode')),'u'=>strtolower($backup->cashier),'type'=>'pos'])
		    					->route('upload-summary', 
		    					[ 'brcode'=>strtolower(session('user.branchcode')),
		    						'u'=>strtolower($backup->cashier),'type'=>'pos', 
		    						'date'=>$backup->date->format('Y-m-d')
		    					])
		    					->with('pos.success', $backup->filename);
		    	/*
					return redirect('/uploader?success='.strtolower(session('user.branchcode')).'-'.strtolower($backup->cashier).'&type=backup')
									->with('backup-success', $backup->filename.' saved on server and processed!');
					*/
				} else
					return redirect()->back()->withErrors(['error'=>'Unknown file!']);

			}
			return redirect()->back()->withErrors(['error'=>'File: '.$request->input('filename').' do not exist! Try to upload again..']);
		} 
		$this->web->deleteFile($filepath); // delete the uploaded file
		return redirect()->back()->withErrors(['error'=>'File '.$request->input('filename').' invalid backup file']);
	}



	/************* for processBackup **************/

	private function emailPayrollBackup($backup, $filepath) {
		
		$data = [
			'branchcode' 	=> session('user.branchcode'),
			'attachment' 	=> $this->payroll->realFullPath($filepath),
			'user'				=> session('user.fullname'),
			'cashier'			=> $backup->cashier,
			'filename'		=> $backup->filename,
			'remarks'			=> $backup->remarks,
			'email'				=> request()->user()->email
		];
		
		try {

			Mail::queue('emails.email_to_hrd', $data, function ($message) use ($data) {
	        $message->subject($data['branchcode'].' '.$data['filename'].' GI PAY BACKUP [gi_pay]');
	        $message->from('no-reply@giligansrestaurant.com', 'GI App - '.$data['branchcode'].' Cashier');
	       	$message->to('giligans.app@gmail.com');
	       	$message->replyTo($data['email'], $data['user']);

	        if (app()->environment()==='production')
	        	$message->to('gi.hrd01@gmail.com');
	       	$message->attach($data['attachment']);
	    });

		} catch (Exception $e) {
			throw $e;
			return false;
		}
		return true;
	}

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
					'filename'		=> $backup->filename,
					'remarks'			=> $backup->remarks,
					'email'				=> request()->user()->email
				];

				$z->close();

				Mail::send('emails.email_to_hrd', $data, function ($message) use ($data) {
		        $message->subject($data['branchcode'].' '.$data['filename'].' PAYROLL BACKUP [payroll]');
		        $message->from('no-reply@giligansrestaurant.com', 'GI App - '.$data['branchcode'].' Cashier');
		      //$message->to('gi.efiles@gmail.com');
		        $message->to('giligans.app@gmail.com');
		       	$message->replyTo($data['email'], $data['user']);
		       	//$message->cc($data['email']);
		        //$message->to('giligans.app@gmail.com');
		        if (app()->environment()==='production')
		        	$message->to('gi.hrd01@gmail.com');
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


  private function isEoD($backup) {
  	try {
  		$code = $this->posUploadRepo->isEoD($backup); 
  	} catch (\Exception $e) {
  		throw $e;
  	}
  }

  private function verifyBackup(Request $request) {
  	try {
  		$code = $this->posUploadRepo->getBackupCode(); 
  	} catch (\Exception $e) {
  		throw $e;
  	}
  	
  	if(strtolower($code)===strtolower($request->user()->branch->code)) {
  		return $code;
  	} else {
  		throw new Exception("Backup file is property of ". $code .' not '.$request->user()->branch->code);
  	}
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
    	'lat' 			=> 0, 
    	'long' 			=> 0, 
    	'remarks' 	=> $request->input('notes'),
    	'userid' 		=> $request->user()->id,
    	'filedate' 	=> $d->format('Y-m-d').' '.Carbon::now()->format('H:i:s'),
    	//'filedate' => $d->format('Y-m-d').' 06:00:00',
    	'cashier' 	=> $request->input('cashier')
    ];

    return $this->posUploadRepo->create($data)?:NULL;
  }

 	private function movedErrorProcessing($filepath, $storage_path) {
  	try {
     	$this->pos->moveFile($this->web->realFullPath($filepath), '..'.DS.'BACKUP_PROCESSING_ERROR'.DS.$storage_path, false); // false = override file!
    } catch (Exception $e) {
			return redirect()->back()->withErrors(['error'=>'Error on moving file on BACKUP_PROCESSING_ERROR folder.']);
    }
    $this->removeExtratedDir();
		DB::rollBack();
    return true;
  }

  private function processed(Backup $posupload){
  	return $this->posUploadRepo->update(['processed'=>1], $posupload->id);
  }

  public function processDailySales(Backup $posupload){
  	//return $this->posUploadRepo->postDailySales($posupload);
  	return $this->posUploadRepo->postNewDailySales($posupload);
  }

  public function processPurchased($date){
  	try {
      $this->posUploadRepo->postPurchased($date);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processTransfer($branchid, $date){
  	try {
      $this->posUploadRepo->postTransfer($branchid, $date, $date);
    } catch(Exception $e) {
      throw $e;    
    }
  }
 
  public function processSalesmtd($date, Backup $backup){
  	try {
      $this->posUploadRepo->postSalesmtd($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processCharges($date, Backup $backup){
  	try {
      $this->posUploadRepo->postCharges($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function backlogTransCount($date, Backup $backup){
  	try {
      $this->posUploadRepo->updateDailySalesTransCount($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function backlogDeposits($date, Backup $backup){
  	try {
      $this->posUploadRepo->updateDeposits($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processKitlog($branchid, $date){
    try {
      return $this->posUploadRepo->postKitlog($branchid, $date);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processCashAudit2($branchid, $date){
    try {
      return $this->posUploadRepo->postCashAudit2($branchid, $date);
    } catch(Exception $e) {
      throw $e;    
    }
  }

   public function backlogSalesmtdChangeItem($branchid, $from, $to) {
    try {
      return $this->posUploadRepo->backlogSalesmtdChangeItem($branchid, $from, $to);
    } catch(Exception $e) {
      throw $e;    
    }
  }


  public function processBankSlip(Request $request) {

		//return $request->all();

		$rules = [
			'filename'		=> 'required',
			'filetype'  	=> 'required',
			'date'				=> 'required|date',
			'type'				=> 'required',
			'time'				=> 'required',
			'amount'			=> 'required',
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

		$date = carbonCheckorNow($request->input('date').' '.$request->input('time'));
		$upload_path = $this->path['temp'].$request->input('filename');

		if ($this->web->exists($upload_path)) { //public/uploads/{branch_code}/{year}/{file}
			$br = strtoupper(session('user.branchcode'));

			$filename = strtoupper($request->input('filename'));
			//if (!starts_with(strtoupper($filename), 'DEPSLP '.$br)) {

				switch ($request->input('type')) {
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
				
				$cnt = $this->countFilenameByDate($date->format('Y-m-d'), $date->format('H:i:s'), $request->input('type'));
				if ($cnt)
					$filename = 'DEPSLP '.$br.' '.$date->format('Ymd His').' '.$type.'-'.$cnt.'.'.$ext;
				else
					$filename = 'DEPSLP '.$br.' '.$date->format('Ymd His').' '.$type.'.'.$ext;
					
			//}

			$storage_path = 'DEPSLP'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$filename; 

			$file = $this->createFileUpload($upload_path, $request, 'C1CCBE28CCDA11E6A3D000FF18C615EC');

			try {
     		$this->files->moveFile($this->web->realFullPath($upload_path), $storage_path, true); // false = override file!
	    } catch(Exception $e) {
				return redirect()->back()
				->with('alert-error', 'Error on moving file. '.$e->getMessage())
				->with('alert-important', ' ');
	    }

	    $depslp = $this->createDepslip($file, $filename);
	    if (!is_null($depslp))
	    	$this->fileUploadRepo->update(['processed'=>1], $file->id);

	    if (app()->environment()==='production')
				event(new DepslpUpload($depslp));

	    return redirect()
    					->route('uploader', ['brcode'=>brcode(),'u'=>strtolower($request->cashier),'type'=>'depslp'])
    					->with('depslp.success', $depslp)
	    				->with('alert-important', '');
	    /*
	    return redirect('/uploader?success='.strtolower($br).'-'.strtolower($request->cashier))
	    ->with('alert-success', $request->filename.' saved on server as '.$filename.'.')
	    ->with('alert-important', '');
			*/
		}
		return redirect()->back()->withErrors(['error'=>'File: '.$request->input('filename').' do not exist! Try to upload again..']);
	}
  /************* for processBankSlip **************/
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

	private function createDepslip($file, $filename){
	 	$data = [
	 	  'branch_id' 		 => session('user.branchid'),
    	'filename' 			 => $filename,
    	'date' 					 => request()->input('date'),
    	'time' 					 => request()->input('time'),
    	'amount' 				 => str_replace(",", "", request()->input('amount')),
    	'type'           => request()->input('type'),
    	'file_upload_id' => $file->id,
    	'terminal'       => clientIP(), //$request->ip(),
    	'remarks' 			 => request()->input('notes'),
    	'user_id' 			 => request()->user()->id,
    	'cashier' 			=> request()->input('cashier')
    ];
    return $this->depslip->create($data)?:NULL;
  }

  private function countFilenameByDate($date, $time, $type) {
  	$d = $this->depslip->findWhere(['date'=>$date, 'time'=>$time, 'type'=>$type]);
		$c = intval(count($d));
		
  	if ($c>0)
			return $c+1;
  		
		return false;
  }
	/************* end: processBankSlip **************/

	public function processCreditCardSlip(Request $request) {

		//return $request->all();

		$rules = [
			'filename'		=> 'required',
			'filetype'  	=> 'required',
			'date'				=> 'required|date',
			'terminal_id'	=> 'required',
			'time'				=> 'required',
			'amount'			=> 'required',
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
			return redirect()->back()->withErrors(['error'=>$request->input('filename').': invalid file extension for Card Settlement Slip.']);
		}

		$date = carbonCheckorNow($request->input('date').' '.$request->input('time'));
		$upload_path = $this->path['temp'].$request->input('filename');

		if ($this->web->exists($upload_path)) { //public/uploads/{branch_code}/{year}/{file}
			$br = strtoupper(session('user.branchcode'));

			$filename = strtoupper($request->input('filename'));
			//if (!starts_with(strtoupper($filename), 'DEPSLP '.$br)) {

				switch ($request->input('terminal_id')) {
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
				
				$cnt = $this->countFilenameByDate2($this->setslp ,$date->format('Y-m-d'), $date->format('H:i:s'), $request->input('terminal_id'));
				if ($cnt)
					$filename = 'SETSLP '.$br.' '.$date->format('Ymd His').' '.$type.'-'.$cnt.'.'.$ext;
				else
					$filename = 'SETSLP '.$br.' '.$date->format('Ymd His').' '.$type.'.'.$ext;
					
			//}

			$storage_path = 'SETSLP'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$filename; 

			$file = $this->createFileUpload($upload_path, $request, '11E8AB712E498A149DCC1C1B0D85A7E0');

			try {
	     		$this->files->moveFile($this->web->realFullPath($upload_path), $storage_path, true); // false = override file!
		    } catch(Exception $e) {
					return redirect()->back()
					->with('alert-error', 'Error on moving file. '.$e->getMessage())
					->with('alert-important', ' ');
		    }

	    $setslp = $this->createCrrslip($file, $filename);
	    if (!is_null($setslp))
	    	$this->fileUploadRepo->update(['processed'=>1], $file->id);

	    //if (app()->environment()==='production')
			//	event(new DepslpUpload($depslp));

	    return redirect()
    					->route('uploader', ['brcode'=>brcode(),'u'=>strtolower($request->cashier),'type'=>'setslp'])
    					->with('setslp.success', $setslp)
	    				->with('alert-important', '');
		}
		return redirect()->back()->withErrors(['error'=>'File: '.$request->input('filename').' do not exist! Try to upload again..']);
	}
	/************* for processCreditCardSlip **************/
	private function countFilenameByDate2($repo, $date, $time, $terminal_id) {
  	$d = $repo->findWhere(['date'=>$date, 'time'=>$time, 'terminal_id'=>$terminal_id]);
		$c = intval(count($d));
  	return ($c>0) 
  		? $c+1
  		: false;
  }

  private function createCrrslip($file, $filename){

	 	$data = [
	 	'branch_id' 			=> session('user.branchid'),
    	'filename' 			=> $filename,
    	'date' 					=> request()->input('date'),
    	'time' 					=> request()->input('time'),
    	'amount' 				=> str_replace(",", "", request()->input('amount')),
    	'terminal_id'		=> request()->input('terminal_id'),
    	'file_upload_id'=> $file->id,
    	'terminal' 			=> clientIP(), //$request->ip(),
    	'remarks' 			=> request()->input('notes'),
    	'user_id' 			=> request()->user()->id,
    	'cashier' 			=> request()->input('cashier')
    ];

    return $this->setslp->create($data)?:NULL;
  }
	/************* end: processCreditCardSlip **************/


  private function processAccountsPayablesRequest(Request $request) {
    // return $request->all();
    $rules = [
      'filename'    => 'required',
      'filetype'    => 'required',
      'date'        => 'required|date',
      'type'        => 'required',
      'doctype'     => 'required',
      'doctypeid'   => 'required',
      'supplier'    => 'required',
      'supplierid'  => 'required',
      'cashier'     => 'required',
      'refno'       => 'required',
    ];

    $messages = [
      'doctypeid.required' => 'Invalid document type! Please choose from the search drop down.',
      'supplierid.required' => 'Invalid supplier! Please choose from the search drop down.',
    ];
    
    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      $this->web->deleteFile($this->path['temp'].$request->input('filename'));
      return redirect()->back()->withErrors($validator);
    }

    $ext = strtolower(pathinfo($request->input('filename'), PATHINFO_EXTENSION));
    if (!in_array(strtolower($ext), ['png', 'jpeg', 'jpg', 'pdf'])) {
      $this->web->deleteFile($this->path['temp'].$request->input('filename'));
      return redirect()->back()->withErrors(['error'=>$request->input('filename').': invalid file extension for AP Files.']);
    }

    $date = carbonCheckorNow($request->input('date').' '.$request->input('time'));
    $upload_path = $this->path['temp'].$request->input('filename');

    $doctype = NULL;
    if ($request->has('doctype') && $request->has('doctypeid'))
      $doctype = \App\Models\Doctype::find($request->input('doctypeid'));
    if ($request->has('doctype') && !$request->has('doctypeid')) {

      // check if may "-" ung doctype input
      $document_code = '';
      $document_name = '';
      $assigned = 0;
      $pos = strpos($request->input('doctype'), '-', 1);
      if ($pos) {
        $xsup = explode('-', $request->input('doctype'), 2);
        if (count($xsup)>1) {
          $document_code = trim($xsup[0]);
          $document_name = trim($xsup[1]);
          $assigned = 1;
        } else
          $document_name = trim($request->input('doctype'));
      } else
        $document_name = trim($request->input('doctype'));

      // if (empty($document_code) && !empty($document_name))
      //   $doctype = \App\Models\Doctype::where(['descriptor'=>$document_name])->first();
      // if (!empty($document_code) && !empty($document_name))
      $doctype = \App\Models\Doctype::where(['code'=>$document_code, 'descriptor'=>$document_name, 'assigned'=>1])->first();

      if (is_null($doctype) && !empty($document_name))
        $doctype = \App\Models\Doctype::where(['descriptor'=>$document_name, 'assigned'=>1])->first();

      if (is_null($doctype))
        $doctype = \App\Models\Doctype::where(['code'=>$document_name, 'assigned'=>1])->first();

      if (is_null($doctype)) {
        if (empty($document_code))
        $document_name = strtoupper(mb_ereg_replace("([\.]{2,})", '', $document_name));

        $doctype = \App\Models\Doctype::create([
          'code'        => strtoupper($document_code),
          'descriptor'  => trim($document_name), 
          'assigned'    => $assigned,
          'branch_id'   => $request->user()->branchid
        ]);
      }
      if (empty($doctype->code)) {
        $doctype->code = strtoupper(filter_filename(initials($doctype->descriptor)));
        $doctype->save();
      }
    }
    if (is_null($doctype))
      return redirect()->back()->withErrors(['error'=>'Could not create Doctype for AP Files.']);

    $supplier = NULL;
    if ($request->has('supplier') && $request->has('supplierid'))
      $supplier = \App\Models\Supplier::where('id', $request->input('supplierid'))->first();
    if ($request->has('supplier') && !$request->has('supplierid')) {

      // check if may "-" ung supplier input
      $supplier_code = '';
      $supplier_name = '';
      $pos = strpos($request->input('supplier'), '-', 1);
      if ($pos) {
        $xsup = explode('-', $request->input('supplier'), 2);
        if (count($xsup)>1) {
          $supplier_code = trim($xsup[0]);
          $supplier_name = trim($xsup[1]);
        } else
          $supplier_name = trim($request->input('supplier'));
      } else
        $supplier_name = trim($request->input('supplier'));

      // if (empty($supplier_code) && !empty($supplier_name))
      //   $supplier = \App\Models\Supplier::where(['descriptor'=>$supplier_name, 'branchid'=>$request->user()->branchid])->first();
      // if (!empty($supplier_code) && !empty($supplier_name))
        $supplier = \App\Models\Supplier::where(['code'=>$supplier_code, 'descriptor'=>$supplier_name, 'branchid'=>$request->user()->branchid])->first();
      // if (is_null($supplier) && !empty($supplier_name))


      if (is_null($supplier)) {
        if (empty($supplier_code))
          $supplier_code = strtoupper(filter_filename(initials($supplier_name)));
        
        $supplier = \App\Models\Supplier::where(['code'=>$supplier_code, 'descriptor'=>$supplier_name, 'branchid'=>$request->user()->branchid])->first();

        if (is_null($supplier)) {
          $supplier_name = strtoupper(mb_ereg_replace("([\.]{2,})", '', $supplier_name));

          $supplier = \App\Models\Supplier::create([
            'code'        => strtoupper($supplier_code),
            'descriptor'  => trim($supplier_name), 
            'branchid'   => $request->user()->branchid
          ]);
        }
      }
      if (empty($supplier->code)) {
        $supplier->code = strtoupper(filter_filename(initials($supplier->descriptor)));
        $supplier->save();
      }
    }
    if (is_null($supplier))
      return redirect()->back()->withErrors(['error'=>'Could not create Supplier for AP Files.']);


    if ($this->web->exists($upload_path)) { //public/uploads/{branch_code}/{year}/{file}
      $br = strtoupper(session('user.branchcode'));

      switch ($request->input('type')) {
        case 1:
          $type = 'C';
          break;
        case 2:
          $type = 'K';
          break;
        case 3:
          $type = 'U';
          break; 
        case 4:
          $type = 'H';
          break;        
        default:
          $type = 'X';
          break;
      }
        
      $filename = $doctype->code.' '.$br.' '.$date->format('Ymd').' '.$type.' '.$supplier->code.' '.strtoupper(filter_filename($request->input('refno'))).'.'.$ext;
          
      $storage_path = 'APU'.DS.$date->format('Y').DS.$br.DS.$date->format('m').DS.$filename; 

      $file = $this->createFileUpload($upload_path, $request, '11EAAB281C1B0D85A7E0E521BE29B63C');

      try {
        $this->files->moveFile($this->web->realFullPath($upload_path), $storage_path, true); // false = override file!
      } catch(Exception $e) {
        return redirect()->back()
        ->with('alert-error', 'Error on moving file. '.$e->getMessage())
        ->with('alert-important', ' ');
      }

      $fp = $this->files->realFullPath($storage_path);
      // xattr_set($fp, 'supplier_id', $supplier->id);
      // xattr_set($fp, 'supplier', $supplier->descriptor);
      // xattr_set($fp, 'doctype_id', $doctype->id);
      // xattr_set($fp, 'doctype', $doctype->descriptor);
      // xattr_set($fp, 'branchcode', $br);

      $apu = $this->createApupload($file, $filename, $doctype->id, $supplier->id);
      if (!is_null($apu))
        $this->fileUploadRepo->update(['processed'=>1], $file->id);

      if (app()->environment()==='production')
        event(new \App\Events\Upload\ApUpload($apu));

      return redirect(brcode().'/apu/'.$apu->lid().'?u='.strtolower($request->cashier).'type=apu')
              ->with('alert-success', $request->filename.' was saved on server as '.$filename)
              ->with('alert-important', '');

      return redirect()
              ->route('uploader', ['brcode'=>brcode(),'u'=>strtolower($request->cashier),'type'=>'apu'])
              ->with('apu.success', $apu)
              ->with('alert-important', '');
    }
    return redirect()->back()->withErrors(['error'=>'File: '.$request->input('filename').' do not exist! Try to upload again..']);
  }
  /************* for processAccountsPayablesRequest **************/
  private function createApupload($file, $filename, $doctypeid, $supplierid){
    $data = [
      'branch_id'      => session('user.branchid'),
      'doctype_id'     => $doctypeid,
      'filename'       => $filename,
      'date'           => request()->input('date'),
      'refno'          => strtoupper(request()->input('refno')),
      'amount'         => str_replace(",", "", request()->input('amount')),
      'type'           => request()->input('type'),
      'supplier_id'    => $supplierid,
      'file_upload_id' => $file->id,
      'remarks'        => request()->input('notes'),
      'user_id'        => request()->user()->id,
      'cashier'       => request()->input('cashier')
    ];
    return \App\Models\ApUpload::create($data)?:NULL;
  }

  public function search(Request $request) {
    if ($request->ajax()) {
      $limit = empty($request->input('maxRows')) ? 10:$request->input('maxRows'); 
      $res = \App\Models\Doctype::where('assigned', '1')
              ->where(function ($query) use ($request) {
                $query->orWhere('code', 'like', '%'.$request->input('q').'%')
                  ->orWhere('descriptor', 'like',  '%'.$request->input('q').'%');
              })
              ->take($limit)
              ->get(['code', 'descriptor', 'id']);

      return $res;
    } 
    return abort(404);
  }

  public function searchSupplier(Request $request) {
    if ($request->ajax()) {
      $limit = empty($request->input('maxRows')) ? 10:$request->input('maxRows'); 
      // $res = \App\Models\Supplier::whereIn('branchid', [$request->user()->branchid, '971077BCA54611E5955600FF59FBB323'])
      $res = \App\Models\Supplier::whereIn('branchid', [$request->user()->branchid])
              ->where(function ($query) use ($request) {
                $query->orWhere('code', 'like', '%'.$request->input('q').'%')
                  ->orWhere('descriptor', 'like',  '%'.$request->input('q').'%');
              })
              ->where('status', 1)
              ->take($limit)
              ->get(['code', 'descriptor', 'id']);

      return $res;
    } 
    return abort(404);
  }
  /************* end: processAccountsPayablesRequest **************/

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


	/************* for processEmployeeTransferRequest **************/
	public function processEmployeeTransferRequest(Request $request, EmpActivity $empActivity) {


		$e = new \App\Http\Controllers\Uploader\EmployeeTransferController;

		return $e->processprocessEmployeeTransferRequest($request, $empActivity);

	}

	/************* end: processEmployeeTransferRequest **************/

	public function getUploadSummary($brcode, Request $request) {
		/*
		$connector = new FilePrintConnector("lpt1");
		$printer = new Printer($connector);
		$printer -> text("       ALQUIROS FOOD CORPORATION\n");
		$printer -> text("         (GILIGAN'S RESTAURANT)\n");
		$printer -> text("             SM by the Bay\n");
		$printer -> text("      BLDG H, UNITS 11-16 BRGY.076\n");
		$printer -> text("      SM BUSINESS PARK, PASAY CITY\n");
		$printer -> text("          #205-257-440-005 VAT\n");
		$printer -> text("            S/N AZLF9270080W\n");
		$printer -> text("             MIN# 090119166\n");
		$printer -> cut();
		$printer -> close();

		return 'printing';
		*/

		$ds = null;
		$date = null;
    $cash_audit = null;
		if ($request->has('date')  && is_iso_date($request->input('date'))) {
			$date = c($request->input('date'));

			$ds = $this->ds->findWhere(['date'=>$request->input('date')])->first();

      if (!is_null($ds))
        $cash_audit = $this->cashAudit->findWhere(['branch_id'=>$ds->branchid ,'date'=>$request->input('date')])->first();
		}

		
		if (app()->environment()==='locals') {

			$date  = c('2017-11-08');

			return $invhdrs = $this->invhdr
													->skipCache()
													->orderBy('refno')
													->with([
														'invdtls.product'=>function($q) {
															$q->select(['code','descriptor','shortdesc','iscombo','id']);
														},
														'scinfos',
														'pwdinfos',
														'orpaydtls',
														'orderhdrs.orderdtls.product'=>function($q) {
															$q->select(['code','descriptor','shortdesc','iscombo','id']);
														}
													])
													->scopeQuery(function($query) {
														return $query->limit(1);
													})
													//->findWhere(['date'=>$date->format('Y-m-d'), 'posted'=>1, 'cancelled'=>0]);
													->findWhere(['date'=>$date->format('Y-m-d'), 'refno'=>'0000027018']);

				//return dd($invhdrs[0]->invdtls);

			/*
			return $orpaydtls = $this->orpaydtl
				->skipCache()
				->whereDate($date)
				->with([
					'invhdr'=>function($q) {
						$q->select(['id'])
							->with(['scinfos'=>function($q){
                $q->where('cancelled', 0);
              }]);
					},
					'bankcard'
				])
				->all();

			return count($orpaydtls[0]->invhdr->scinfos);
				//->findWhere(['orpaydtl.cancelled'=>'0']);

			return view('backups.upload-summary', compact('date'))->with('ds', $ds);
			
			return $orpaydtls = $this->orpaydtl
				->skipCache()
				->whereHas('invhdr', function($query) use ($date) {
	        $query->where('date', $date->format('Y-m-d'))
	        			->where('posted', 1);
	      })
				->with([
					'invhdr'=>function($q) {
						$q->select(['refno', 'date', 'tableno', 'timestart','id']);
					}
				])
				->all();
			
			*/


			return $invdtls = $this->invdtl
				->skipCache()
				->whereDate($date)
				->with([
					'invhdr'=>function($q) {
						$q->select(['refno', 'date', 'tableno', 'timestart','posted','id']);
					}, 
					'product'=>function($q) {
						$q->select(['code', 'shortdesc', 'prodcatid', 'sectionid', 'id'])
							->with('prodcat')
							->with(['combos'=>function($q){
								$q->with('product')
								->orderBy('seqno');
							}])
							->with(['menuprod'=>function($q){
								$q->with('menucat')
									->orderBy('seqno');
							}]);
					}
				])
				->all();
		}
		


		return view('backups.upload-summary', compact('date'))->with('ds', $ds)->with('cash_audit', $cash_audit);
	}



















	private function logAction($action, $log) {
		$logfile = base_path().DS.'logs'.DS.brcode().DS.now().'-log.txt';

		$dir = pathinfo($logfile, PATHINFO_DIRNAME);

		if(!is_dir($dir))
			mkdir($dir, 0775, true);

		$new = file_exists($logfile) ? false : true;
		if($new){
			$handle = fopen($logfile, 'w+');
			chmod($logfile, 0775);
		} else
			$handle = fopen($logfile, 'a');

		$ip = clientIP();
		$brw = $_SERVER['HTTP_USER_AGENT'];
		$content = date('r')." | {$ip} | {$action} | {$log} \t {$brw}\n";
    fwrite($handle, $content);
    fclose($handle);
	}	



  public function testMail() {

    $data = [
      'body' => 'this is a test,'
    ];

    try {

      $m = \Mail::queue('emails.notifier', $data, function ($message) {
        $message->subject('Test Emailss');
        $message->from('test@gmail.com');
        $message->to('jefferson.salunga@gmail.com');
      });

    } catch (\Exception $e) {
      return $e->getMessage();
    }

    return $m;
    
  }



}