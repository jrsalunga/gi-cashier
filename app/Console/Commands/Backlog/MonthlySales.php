<?php namespace App\Console\Commands\Backlog;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use App\Models\DailySales as DS;
use Exception;
use Illuminate\Console\Command;
use App\Repositories\DailySales2Repository as DSRepo;
use App\Repositories\MonthlySalesRepository as MSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use App\Repositories\DateRange as DR;

class MonthlySales extends Command
{
  /**
   * The name and signature of the console command.
   *  php artisan backlog:ds ara gc021618.zip 2018-02-15
   * @var string
   */
  protected $signature = 'backlog:ms {brcode : BrCode} {year : YYYY}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Backlog process monthly sales summary with rank';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $sales;
  protected $ds;
  protected $dr;
  protected $ms;
  protected $posUploadRepo;

  public function __construct(DSRepo $ds, MSRepo $ms, SalesRepo $sales, PosUploadRepo $posUploadRepo, DR $dr)
  {
    parent::__construct();
    $this->sales = $sales;
    $this->ds = $ds;
    $this->dr = $dr;
    $this->ms = $ms;
    $this->posUploadRepo = $posUploadRepo;
  }

  public function handle() {


    $br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
    if (!$br) {
      $this->info('Invalid Branch Code.');
      exit;
    }
      
    $this->dr->fr = c($this->argument('year').'-01-01');
    $this->dr->to = c($this->argument('year').'-12-31');


    $this->info($br->code);



    foreach ($this->dr->monthInterval() as $key => $date) {
      $date->lastOfMonth();
      $this->info($br->code.' '.$date);

      if ($br->opendate()->copy()->startOfMonth()->gt($date)) {
        $this->info('Unable to process. Not yet open!');
      } else {
        
        try {
          $ms = $this->ds->computeMonthTotal($date, $br->id);
        } catch (Exception $e) {
          $this->info($e->getMessage());
        }
        
        
        if (is_null($ms)) {
          $this->info('NULL');
          $this->ms->firstOrNewField(['date'=>$date->format('Y-m-d'), 'branch_id'=>$br->id], ['date', 'branch_id']);
        } else {
          $this->info($ms->date);
          $this->ms->firstOrNewField(array_except($ms->toArray(), ['year', 'month']), ['date', 'branch_id']);
          $this->ms->rank($ms->date);
          //$this->info(json_encode($ms));
        }

      }
    }

    return 0;

    try {
      $month = $this->ds->computeMonthTotal($event->backup->filedate, $event->backup->branchid);
    } catch (Exception $e) {
      //logAction('onDailySalesSuccess Error', $e->getMessage());
      $data = [
        'user'      => request()->user()->name,
        'cashier'   => $event->backup->cashier,
        'filename'  => $event->backup->filename,
        'body'      => 'Error onDailySalesSuccess '.$event->backup->branchid.' '.$event->backup->filedate,
      ];

      $this->mailer->queue('emails.notifier', $data, function ($message) use ($event){
        $message->subject('Backup Upload DailySales Process Error');
        $message->from($event->user->email, $event->user->name.' ('.$event->user->email.')');
        $message->to('giligans.app@gmail.com');
      });
    
    } finally {
      //logAction('onDailySalesSuccess', $event->backup->filedate->format('Y-m-d').' '.request()->user()->branchid.' '.json_encode($month));
      $this->ms->firstOrNewField(array_except($month->toArray(), ['year', 'month']), ['date', 'branch_id']);
      //logAction('onDailySalesSuccess', 'rank');
      $this->ms->rank($month->date);
    }





  	

  	$backup = strtoupper($this->argument('backup'));
  	if (!is_pos_backup($backup)) {
  		$this->info('Invalid Backup.');
      exit;
  	}
  	$d = backup_to_carbon_date($backup);

  	
  	$locator = new Locator('pos');
  	$path = $br->code.DS.$d->format('Y').DS.$d->format('m').DS.'GC'.$d->format('mdy').'.ZIP';
  	if (!$locator->exists($path)) {
  		$this->info('Backup '.$path.' do not exist.');
      exit;
  	}


    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->info('Invalid date.');
      exit;
    }


    $to = Carbon::parse($date);
  	if ($to->gt($d) || $to->format('Y-m')!=$d->format('Y-m')) {
      $this->info('No data for the date of '.$date.' on '.$backup.' backup');
      exit;
  	}   


    //$this->info($locator->realFullPath($path)); exit;
  	
  	$bckup = $this->posUploadRepo->findWhere(['branchid'=>$br->id, 'filename'=>$backup])->first();
  	if (!$bckup) {
    	$this->info('No record found on database.'); 
    	exit;
  	}

  	if (!$this->extract($locator->realFullPath($path), $br->code)) {
			$this->info('Unable to extract '. $backup .', the backup maybe corrupted. Try to generate another backup file and try to re-upload.');
			exit;
		}


    $this->info('start processing...');

		DB::beginTransaction();

    $this->info('extracting cash audit...');
    try {
      $r = $this->processCashAudit($to, $bckup);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }
    //$this->info($r);
		
    $this->info('extracting purchased...');
    try {
      $this->processPurchased($to, $bckup);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }
    $this->info('success purchased...');
		
    $this->info('extracting salesmtd...');
		try {
			$this->processSalesmtd($to, $bckup);
		} catch (Exception $e) {
			$this->info($e->getMessage());
    	$this->removeExtratedDir();
    	DB::rollback();
    	exit;
		}

    $this->info('extracting charges...');
		try {
			$this->processCharges($to, $bckup);
		} catch (Exception $e) {
			$this->info($e->getMessage());
    	$this->removeExtratedDir();
    	DB::rollback();
    	exit;
		}


    //DB::rollback();
		DB::commit();
      
      
    $this->info('done');
    $this->removeExtratedDir();
    exit;
  }





  private function extract($filepath, $brcode) {
  	return $this->posUploadRepo->extract($filepath, 'admate', false, $brcode);	
  }

  private function removeExtratedDir() {
  	return $this->posUploadRepo->removeExtratedDir();
  }

  public function processCashAudit($date, $backup) {
    try {
      return $this->posUploadRepo->postCashAudit($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processSalesmtd($date, $backup) {
  	try {
      $this->posUploadRepo->postSalesmtd($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processPurchased($date, $backup) {
  	try {
      $this->posUploadRepo->postPurchased2($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processCharges($date, $backup) {
  	try {
      $this->posUploadRepo->postCharges($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }




}