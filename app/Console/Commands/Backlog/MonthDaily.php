<?php namespace App\Console\Commands\Backlog;

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
  protected $signature = 'backlog:month {brcode : Branch Code} {date : YYYY-MM-DD}';

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

    $d = Carbon::parse($date);
    $f = Carbon::parse($date)->startOfMonth();
    $t = Carbon::parse($date)->endOfMonth();

    $this->info($f->format('Y-m-d'));


    $locator = new Locator('pos');
    $path = $br->code.DS.$t->format('Y').DS.$t->format('m').DS.'GC'.$t->format('mdy').'.ZIP';
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

    $backup = \App\Models\Backup::where('branchid', $br->id)->where('filename', 'GC'.$t->format('mdy').'.ZIP')->first();

    if (is_null($backup)) {
      $this->info('No backup log found on '. $t->format('Y-m-d'));
      exit;
    }
    //$this->posUploadRepo->postNewDailySales($br->id, Carbon::parse($date), $this);

    
    DB::beginTransaction();
    

    
    $this->info('extracting purchased...');
    try {
      $r = $this->backlogPurchased($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    } 

    $this->info('extracting trasfer...');
    try {
      $r = $this->backlogTransfer($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    } finally {
      foreach (dateInterval($f, $t) as $key => $date)
        event(new \App\Events\Process\AggregatorDaily('purchase', $date, $backup->branchid));
    }

   
    
    $this->info('extracting cash audit...');
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
    
    
    $this->info('working on events...');
    $this->info('DailySalesSuccess');
    event(new \App\Events\Backup\DailySalesSuccess($backup));
    $this->info('AggregateComponentMonthly');
    event(new \App\Events\Process\AggregateComponentMonthly($backup->date, $backup->branchid));
    $this->info('AggregateMonthlyExpense');
    event(new \App\Events\Process\AggregateMonthlyExpense($backup->date, $backup->branchid));
    $this->info('AggregatorMonthly trans-expense');
    event(new \App\Events\Process\AggregatorMonthly('trans-expense', $backup->date, $backup->branchid));
    $this->info('AggregatorMonthly product');
    event(new \App\Events\Process\AggregatorMonthly('product', $backup->date, $backup->branchid)); // recompute Monthly Expense
    $this->info('AggregatorMonthly prodcat');
    event(new \App\Events\Process\AggregatorMonthly('prodcat', $backup->date, $backup->branchid)); 
    $this->info('AggregatorMonthly groupies');
    event(new \App\Events\Process\AggregatorMonthly('groupies', $backup->date, $backup->branchid));
    $this->info('RankMonthlyProduct');
    event(new \App\Events\Process\RankMonthlyProduct($backup->date, $backup->branchid));
    
    
    
    

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

  




}