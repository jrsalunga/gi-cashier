<?php namespace App\Console\Commands\Cron;

use App\Events\Backup\DailySalesSuccess2;
use App\Events\Process\AggregateComponentMonthly;
use App\Events\Process\AggregateMonthlyExpense;
use App\Events\Process\AggregatorDaily;
use App\Events\Process\AggregatorMonthly;
use App\Events\Process\RankMonthlyProduct;
use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use App\Events\Notifier;

class BacklogMonth extends Command
{

	protected $signature = 'process:backlog-month';
  protected $description = 'process the CRON backlog month based on for_process.type=3';
  protected $process;
  protected $sales;
  protected $ds;
  protected $posUploadRepo;

  public function __construct(Process $process, DSRepo $ds, SalesRepo $sales, PosUploadRepo $posUploadRepo) {
    parent::__construct();
    $this->process = $process;
    $this->sales = $sales;
    $this->ds = $ds;
    $this->posUploadRepo = $posUploadRepo;
  }

  public function handle() {

    $process = $this->process
                    ->where('processed', '0')
                    ->where('type', '3')
                    ->orderBy('code')
                    ->orderBy('filedate')
                    ->first();

    if (!is_null($process)) {

	    
    	$br = Branch::where('code', strtoupper($process->code))->first();
  		if (!$br) {
  			$this->notify('Branch not found.');
  			$process->processed = 3;
  			$process->save();
	      exit;
	  	}

	  	$f = Carbon::parse($process->filedate->format('Y-m-d'))->startOfMonth();
    	$t = Carbon::parse($process->filedate->format('Y-m-d'))->endOfMonth();

    	$locator = new Locator('pos');
	    $path = $br->code.DS.$t->format('Y').DS.$t->format('m').DS.'GC'.$t->format('mdy').'.ZIP';
	    if (!$locator->exists($path)) {
	      $this->notify('Backup '.$path.' do not exist.');
  			$process->processed = 3;
  			$process->save();
	      exit;
	    }

	    if(app()->environment()=='local')
	    	$this->info('extracting...');
	    if (!$this->extract($locator->realFullPath($path), $br->code)) {
	      $this->notify('Unable to extract '. $path .', the backup maybe corrupted. Try to generate another backup file and try to re-upload.');
  			$process->processed = 3;
  			$process->save();
	      exit;
	    }

	    if(app()->environment()=='local')
	    	$this->info('start processing...');

	  // set to know the backup is on process
	  $process->processed = 2;
  	$process->save();


	  DB::beginTransaction();
    
    $this->info('extracting purchased...');
    try {
      $r = $this->backlogPurchased($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->notify('purchased:'.$e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      $process->processed = 3;
  		$process->save();
      exit;
    }
    
    $this->info('extracting transfer...');
    try {
      $r = $this->backlogTransfer($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->notify('transfer:'.$e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      $process->processed = 3;
  		$process->save();
      exit;
    } finally {
      foreach (dateInterval($f, $t) as $key => $date)
        event(new AggregatorDaily('purchase', $date, $br->id));
    }
    
    $this->info('extracting daily sales on cash audit...');
    try {
      $r = $this->backlogDailySales($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->notify('csh_audt:'.$e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      $process->processed = 3;
  		$process->save();
      exit;
    }
    
    $this->info('extracting salesmtd...');
    try {
      $r = $this->backlogSalesmtd($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->notify('salesmtd:'.$e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      $process->processed = 3;
  		$process->save();
      exit;
    }

    $this->info('extracting charges...');
    try {
      $r = $this->backlogCharges($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->notify('charges:'.$e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      $process->processed = 3;
  		$process->save();
      exit;
    }

    $this->info('extracting cash audit...');
    try {
      $this->backlogCashAudit2($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }

    $this->info('extracting kitchen log...');
    $kl = 0;
    try {
      $kl = $this->backlogKitlog($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }
    // re Run the Backlog\Kitlog to process all 
    // this will process only the kitlog on backup loaded on storage
    if($kl>0) {
      event(new \App\Events\Process\AggregatorKitlog('month_kitlog_food', $t, $br->id));
      event(new \App\Events\Process\AggregatorKitlog('month_kitlog_area', $t, $br->id));
    }

    foreach (dateInterval($f, $t) as $key => $date) {
      $this->info('working on events: '.$date);
      $this->info('DailySalesSuccess');
      event(new DailySalesSuccess2($date, $br->id));
    }

    $this->info('AggregateComponentMonthly');
    event(new AggregateComponentMonthly($t, $br->id));
    $this->info('AggregateMonthlyExpense');
    event(new AggregateMonthlyExpense($t, $br->id));
    $this->info('AggregatorMonthly trans-expense');
    event(new AggregatorMonthly('trans-expense', $t, $br->id));
    $this->info('AggregatorMonthly product');
    event(new AggregatorMonthly('product', $t, $br->id));
    $this->info('AggregatorMonthly prodcat');
    event(new AggregatorMonthly('prodcat', $t, $br->id));
    $this->info('AggregatorMonthly groupies');
    event(new AggregatorMonthly('groupies', $t, $br->id));
    $this->info('AggregatorMonthly change_item groupies');
    event(new AggregatorMonthly('change_item', $t, $br->id));
    $this->info('RankMonthlyProduct');
    event(new RankMonthlyProduct($t, $br->id));
    

    DB::commit();




	    //if(app()->environment()=='local') {
	    	$this->info('removing directory...');
	    //}
	    $this->removeExtratedDir();
    	
    	$process->processed = 1;
    	$process->save();

	    $this->info('done: '.$process->code.' '.$process->filedate);
	    
	    exit;
	    

    	$this->info(json_encode($process));
    } else {
    	$this->info('no more process');
    }

  }
  
  private function notify($msg) {
  	if(app()->environment()=='production')
      event(new Notifier('Cron\BacklogMonth: '.$msg));
    else
      $this->error($msg);
  }

  private function extract($filepath, $brcode) {
  	return $this->posUploadRepo->extract($filepath, 'admate', false, $brcode);	
  }

  private function removeExtratedDir() {
  	return $this->posUploadRepo->removeExtratedDir();
  }

  public function backlogDailySales($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogDailySales($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function backlogSalesmtd($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogSalesmtd($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function backlogCharges($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogCharges($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function backlogPurchased($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogPurchased($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function backlogTransfer($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogTransfer($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function backlogCashAudit2($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogCashAudit2($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }
  
  public function backlogKitlog($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogKitlog($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }




}