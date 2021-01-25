<?php namespace App\Console\Commands\Backlog;

use App\Events\Backup\DailySalesSuccess2;
use App\Events\Posting\SalesmtdSuccess;
use App\Events\Process\AggregateComponentMonthly;
use App\Events\Process\AggregateMonthlyExpense;
use App\Events\Process\AggregatorDaily;
use App\Events\Process\AggregatorMonthly;
use App\Events\Process\RankMonthlyProduct;
use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use App\Models\DailySales as DS;
use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;

class DailySales extends Command
{
  /**
   * The name and signature of the console command.
   *  php artisan backlog:ds ara gc021618.zip 2018-02-15
   * @var string
   */
  protected $signature = 'backlog:ds {brcode : Branch Code} {backup : Backup File} {date : YYYY-MM-DD}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process dailysales backlog';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $sales;
  protected $ds;
  protected $posUploadRepo;

  public function __construct(DSRepo $ds, SalesRepo $sales, PosUploadRepo $posUploadRepo)
  {
    parent::__construct();
    $this->sales = $sales;
    $this->ds = $ds;
    $this->posUploadRepo = $posUploadRepo;
  }

  public function handle() {


  	$br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
  	if (!$br) {
  		$this->info('Invalid Branch Code.');
      exit;
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

    //return dd($bckup);

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
		} finally {
      event(new SalesmtdSuccess($bckup));
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


    $this->info('extracting transfer...');
    try {
      $this->processTransfer($br->id, $to);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }

    // push emp meal on purchase
    event('transfer.empmeal', ['data'=>['branch_id'=> $bckup->branchid, 'date'=>$to, 'suppliercode'=>$br->code]]);
    // compute delivery fee (GrabFood, Food Panda)   \\App\Listeners\BackupEventListener
    event('deliveryfee', ['data'=>['branch_id'=> $bckup->branchid, 'date'=>$to]]);


    event(new AggregatorDaily('purchase', $to, $bckup->branchid));

    event(new \App\Events\Process\AggregateComponentDaily($to, $bckup->branchid)); // recompute Daily Component
    event(new \App\Events\Process\AggregateDailyExpense($to, $bckup->branchid)); // recompute Daily Expense
    event(new \App\Events\Process\AggregatorDaily('trans-expense', $to, $bckup->branchid)); // recompute Daily Transfered and update day_expense
    event(new \App\Events\Process\AggregatorDaily('prodcat', $to, $bckup->branchid)); 

    event(new DailySalesSuccess2($to, $bckup->branchid)); // recompute Monthlysales
    event(new AggregateComponentMonthly($to, $bckup->branchid)); // recompute Monthly Component
    event(new AggregateMonthlyExpense($to, $bckup->branchid)); // recompute Monthly Expense
    event(new AggregatorMonthly('trans-expense', $to, $bckup->branchid));
    event(new AggregatorMonthly('product', $to, $bckup->branchid)); // recompute Monthly Expense
    event(new AggregatorMonthly('prodcat', $to, $bckup->branchid));
    event(new AggregatorMonthly('groupies', $to, $bckup->branchid));
    event(new \App\Events\Process\AggregatorMonthly('change_item', $to, $bckup->branchid));
    event(new \App\Events\Process\AggregatorMonthly('cash_audit', $to, $bckup->branchid));
    event(new RankMonthlyProduct($to, $bckup->branchid));

    event(new \App\Events\Process\AggregatorMonthly('charge-type', $to, $bckup->branchid));
    event(new \App\Events\Process\AggregatorMonthly('sale-type', $to, $bckup->branchid));
    event(new \App\Events\Process\AggregatorMonthly('card-type', $to, $bckup->branchid));
   

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

  public function processCashAudit($date, $backup){
    try {
      return $this->posUploadRepo->postCashAudit($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processSalesmtd($date, $backup){
  	try {
      $this->posUploadRepo->postSalesmtd($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processPurchased($date, $backup){
  	try {
      $this->posUploadRepo->postPurchased2($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processCharges($date, $backup){
  	try {
      $this->posUploadRepo->postCharges($date, $backup);
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




}