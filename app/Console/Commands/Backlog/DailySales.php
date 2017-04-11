<?php namespace App\Console\Commands\Backlog;

use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use App\Models\DailySales as DS;
use App\Models\Branch;
use Carbon\Carbon;
use App\Helpers\Locator;
use DB;

class DailySales extends Command
{
  /**
   * The name and signature of the console command.
   *
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

  	if (!$this->extract($locator->realFullPath($path), $br->code)) {
			$this->info('Unable to extract '. $backup .', the backup maybe corrupted. Try to generate another backup file and try to re-upload.');
			exit;
		}


    $this->info('start processing...');

		DB::beginTransaction();

		
    $this->info('extracting purchased...');
		try {
			$this->processPurchased($to, $bckup);
		} catch (Exception $e) {
			$this->info($e->getMessage());
    	$this->removeExtratedDir();
    	DB::rollback();
    	exit;
		}
		
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




}