<?php namespace App\Console\Commands\Cron;

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

class BacklogCharges extends Command
{

	protected $signature = 'process:backlog-charges';
  protected $description = 'process the backlog charges';
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
                    ->where('type', '4')
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
      event(new Notifier('Cron\BacklogCharges: '.$msg));
    else
      $this->error($msg);
  }

  private function extract($filepath, $brcode) {
  	return $this->posUploadRepo->extract($filepath, 'admate', false, $brcode);	
  }

  private function removeExtratedDir() {
  	return $this->posUploadRepo->removeExtratedDir();
  }

  

  public function backlogCharges($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogCharges($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  
  





}