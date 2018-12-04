<?php namespace App\Console\Commands\Checker;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use App\Models\DailySales as DS;
use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;

class PosVersion extends Command
{
  /**
   * The name and signature of the console command.
   *  php artisan backlog:ds ara gc021618.zip 2018-02-15
   * @var string
   */
  protected $signature = 'check:pos-version {date : YYYY-MM-DD}';

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


  	


    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->info('Invalid date.');
      exit;
    }
  	

  	$to = Carbon::parse($date);
    if ($to->gt(c())) {
      $this->info('No data for the date of '.$date.' on '.$backup.' backup');
      exit;
    }

    $arr = [];


    foreach (Branch::orderBy('code')->get() as $key => $br) {

      $locator = new Locator('pos');
      $path = $br->code.DS.$to->format('Y').DS.$to->format('m').DS.'GC'.$to->format('mdy').'.ZIP';
      //$this->info($path);
      if (!$locator->exists($path)) {
        $this->info($br->code.' - backup '.$path.' do not exist.');
      } else {
        
        //$this->info('Backup '.$path);




        if (!$this->extract($locator->realFullPath($path), $br->code)) {
          $this->info('Unable to extract '. $backup .', the backup maybe corrupted. Try to generate another backup file and try to re-upload.');
        }

        $this->loadSysinfo();

        $this->info($br->code.' - '.$this->getSysinfo()->posupdate);

        $v = $this->getSysinfo()->posupdate;
        if (array_key_exists($v, $arr)) {
          $arr[$v]['ctr']++;
          array_push($arr[$v]['branch'], $br->code);

        } else {
          $arr[$v]['ctr'] = 1;
          $arr[$v]['branch'] = [];
          array_push($arr[$v]['branch'], $br->code);

        }

        $this->removeExtratedDir();
      }
    }  

    foreach ($arr as $v => $value) {
      # code...
    $this->info($v.': '.$arr[$v]['ctr']);
    $this->info(json_encode($arr[$v]['branch']));
    }



  	/*
  
    f
  	
  	$locator = new Locator('pos');
  	$path = $br->code.DS.$to->format('Y').DS.$to->format('m').DS.'GC'.$to->format('mdy').'.ZIP';
  	if (!$locator->exists($path)) {
  		$this->info('Backup '.$path.' do not exist.');
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

   */
      
      
    $this->info('done');
    //$this->removeExtratedDir();
    exit;
  }

   private function extract($filepath, $brcode) {
    return $this->posUploadRepo->extract($filepath, 'admate', false, $brcode);  
  }

  private function removeExtratedDir() {
    return $this->posUploadRepo->removeExtratedDir();
  }

  private function getSysinfo() {
    return $this->posUploadRepo->sysinfo;
  }

  private function loadSysinfo() {
    return $this->posUploadRepo->getBackupCode();
  }



 
  




}