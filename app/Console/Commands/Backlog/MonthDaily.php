<?php namespace App\Console\Commands\Backlog;

use App\Events\Backup\DailySalesSuccess;
use App\Events\Process\AggregateComponentMonthly;
use App\Events\Process\AggregateMonthlyExpense;
use App\Events\Process\AggregatorDaily;
use App\Events\Process\AggregatorMonthly;
use App\Events\Process\RankMonthlyProduct;
use App\Models\Backup;
use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use App\Models\DailySales as DS;
use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\DailySales2Repository as DSRepo2;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;

class MonthDaily extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'backlog:month {brcode : Branch Code} {date : YYYY-MM-DD}  {--dateTo= : Date To}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process daily data on one month';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $sales;
  protected $ds;
  protected $ds2;
  protected $posUploadRepo;

  public function __construct(DSRepo $ds, SalesRepo $sales, PosUploadRepo $posUploadRepo, DSRepo2 $ds2)
  {
    parent::__construct();
    $this->sales = $sales;
    $this->ds = $ds;
    $this->ds2 = $ds2;
    $this->posUploadRepo = $posUploadRepo;
  }

  public function handle() {


  	$br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
  	if (!$br) {
  		$this->info('Invalid Branch Code.');
      exit;
  	}
  	
    /*
  	$backup = strtoupper($this->argument('backup'));
  	if (!is_pos_backup($backup)) {
  		$this->info('Invalid Backup.');
      exit;
  	}
  	$d = backup_to_carbon_date($backup);
    */

    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->info('Invalid date.');
      exit;
    }

    if (is_null($this->option('dateTo'))) {
      $d = Carbon::parse($date);
      $f = Carbon::parse($date)->startOfMonth();
      $t = Carbon::parse($date)->endOfMonth();
    } else {
      if (!is_iso_date($this->option('dateTo'))) {
        $this->info('Invalid date.');
        exit;
      }

      $d = Carbon::parse($date);
      $f = Carbon::parse($date);
      $t = Carbon::parse($this->option('dateTo'));
    }



    $this->info('d:'.$d->format('Y-m-d').' f:'.$f->format('Y-m-d').' t:'.$t->format('Y-m-d'));


    // exit;


    $this->info($f->format('Y-m-d'));


    $locator = new Locator('pos');
    $path = $br->code.DS.$t->format('Y').DS.$t->format('m').DS.'GC'.$t->format('mdy').'.ZIP';
    $this->info('Location: '.$locator->realFullPath($path));
    if (!$locator->exists($path)) {
      $t = $d;
      $path = $br->code.DS.$t->format('Y').DS.$t->format('m').DS.'GC'.$t->format('mdy').'.ZIP';
      if (!$locator->exists($path)) {
        $t = $d;
        $this->info('Backup '.$path.' do not exist.');
        exit;
      } else {
        $this->info($t->format('Y-m-d'));
      }
    } else {
      $this->info($t->format('Y-m-d'));
    }
    $this->info($path);

    if (!$this->extract($locator->realFullPath($path), $br->code)) {
      $this->info('Unable to extract '. $backup .', the backup maybe corrupted. Try to generate another backup file and try to re-upload.');
      exit;
    }


    $this->info('start processing...');

    $backup = Backup::where('branchid', $br->id)->where('filename', 'GC'.$t->format('mdy').'.ZIP')->first();

    if (is_null($backup)) {
      $this->info('No backup log found on '. $t->format('Y-m-d'));
      exit;
    }
    //$this->posUploadRepo->postNewDailySales($br->id, Carbon::parse($date), $this);
    

    //return dd($this->posUploadRepo->extracted_path);

    
    DB::beginTransaction();


    $this->info('extracting dailysales on cash audit...');
    try {
      $r = $this->backlogDailySales($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }

    $this->info('extracting salesmtd...');
    try {
      $r = $this->backlogSalesmtd($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }


    $this->info('extracting charges...');
    try {
      $r = $this->backlogCharges($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }
    
    $this->info('extracting purchased...');
    try {
      $r = $this->backlogPurchased($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    } 

    $this->info('extracting trasfer........');
    try {
      $r = $this->backlogTransfer($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    } finally {
      foreach (dateInterval($f, $t) as $key => $date) {
        $this->info('AggregatorDaily::purchase '.$date);
        event(new AggregatorDaily('purchase', $date, $backup->branchid));
        // logAction('fire empmeal', $date);
        // push emp meal on purchase
        // event('transfer.empmeal', ['data'=>['branch_id'=> $backup->branchid, 'date'=>$date, 'suppliercode'=>$br->code]]);
        // logAction('fire deliveryfee', $date);
        // compute delivery fee (GrabFood, Food Panda)   \\App\Listeners\BackupEventListener
        event('direct-profit', ['data'=>['branch_id'=> $backup->branchid, 'date'=>$date]]);
        $this->info('AggregatorDaily::deliveryfee '.$date);
        event('deliveryfee', ['data'=>['branch_id'=> $backup->branchid, 'date'=>$date]]);
      }
    }
    $this->info('done extracting transfer...................');
    
    $this->info('extracting cash audit...');
    try {
      $this->backlogCashAudit2($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }
    
    
    // disabled 01/31/2024 *******************/
    // $this->info('extracting kitchen log...');
    // $kl = 0;
    // try {
    //   $kl = $this->backlogKitlog($br->id, $f, $t, $this);
    // } catch (Exception $e) {
    //   $this->info($e->getMessage());
    //   $this->removeExtratedDir();
    //   DB::rollback();
    //   exit;
    // }
    // // re Run the Backlog\Kitlog to process all 
    // // this will process only the kitlog on backup loaded on storage
    // if($kl>0) {
    //   event(new \App\Events\Process\AggregatorKitlog('month_kitlog_food', $t, $br->id));
    //   event(new \App\Events\Process\AggregatorKitlog('month_kitlog_area', $t, $br->id));
    // }
    
    
    $this->info('working on events...');


    event(new \App\Events\Process\AggregateComponentDaily($backup->date, $backup->branchid)); // recompute Daily Component
    event(new \App\Events\Process\AggregateDailyExpense($backup->date, $backup->branchid)); // recompute Daily Expense
    event(new \App\Events\Process\AggregatorDaily('trans-expense', $backup->date, $backup->branchid)); // recompute Daily Transfered and update day_expense
    event(new \App\Events\Process\AggregatorDaily('prodcat', $backup->date, $backup->branchid)); 

    // event(new \App\Events\Process\AggregatorDaily('change_item', $backup->date, $backup->branchid)); // update ds
    $this->info('DailySalesSuccess');
    event(new DailySalesSuccess($backup));
    $this->info('AggregateComponentMonthly');
    event(new AggregateComponentMonthly($backup->date, $backup->branchid));
    $this->info('AggregateMonthlyExpense');
    event(new AggregateMonthlyExpense($backup->date, $backup->branchid));
    $this->info('AggregatorMonthly trans-expense');
    event(new AggregatorMonthly('trans-expense', $backup->date, $backup->branchid));
    $this->info('AggregatorMonthly product');
    event(new AggregatorMonthly('product', $backup->date, $backup->branchid)); // recompute Monthly Expense
    $this->info('AggregatorMonthly prodcat');
    event(new AggregatorMonthly('prodcat', $backup->date, $backup->branchid));
    $this->info('AggregatorMonthly groupies');
    event(new AggregatorMonthly('groupies', $backup->date, $backup->branchid));
    $this->info('AggregatorMonthly');
    event(new AggregatorMonthly('change_item', $backup->date, $backup->branchid));
    $this->info('AggregatorMonthly cash-audit');
    event(new \App\Events\Process\AggregatorMonthly('cash_audit', $backup->date, $backup->branchid));
    // $this->info('RankMonthlyProduct'); //AggregatorDailyEventListener@rankMonthlyProduct
    // event(new RankMonthlyProduct($backup->date, $backup->branchid));


    event(new \App\Events\Process\AggregatorMonthly('charge-type', $backup->date, $backup->branchid));
    event(new \App\Events\Process\AggregatorMonthly('sale-type', $backup->date, $backup->branchid));
    event(new \App\Events\Process\AggregatorMonthly('card-type', $backup->date, $backup->branchid));
    event(new \App\Events\Process\AggregatorMonthly('disc-type', $backup->date, $backup->branchid));

    // disabled 01/31/2024 *******************/
    // $this->info('AggregatorKitlog');
    // event(new \App\Events\Process\AggregatorKitlog('dataset_area', $t, $br->id));
    // event(new \App\Events\Process\AggregatorKitlog('dataset_food', $t, $br->id));
    // event(new \App\Events\Process\AggregatorKitlog('dataset_area', $t, NULL));
    // event(new \App\Events\Process\AggregatorKitlog('dataset_food', $t, NULL));
    

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