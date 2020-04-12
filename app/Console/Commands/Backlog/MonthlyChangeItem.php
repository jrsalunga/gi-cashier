<?php namespace App\Console\Commands\Backlog;

use App\Models\Backup;
use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Repositories\PosUploadRepository as PosUploadRepo;

class MonthlyChangeItem extends Command
{

  // run mysql: update product set uprice = 0, uprice = 0; first 
  protected $signature = 'backlog:change-item {brcode : Branch Code} {date : YYYY-MM-DD}';
  protected $description = '';

  protected $posUploadRepo;

  public function __construct(PosUploadRepo $posUploadRepo)
  {
    parent::__construct();
    $this->posUploadRepo = $posUploadRepo;
  }

  public function handle() {


  	$br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
  	if (!$br) {
  		$this->info('Invalid Branch Code.');
      exit;
  	}
  	
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

    if (app()->environment('production')) {
      $backup = Backup::where('branchid', $br->id)->where('filename', 'GC'.$t->format('mdy').'.ZIP')->first();

      if (is_null($backup)) {
        $this->info('No backup log found on '. $t->format('Y-m-d'));
        exit;
      }
    }

    
    DB::beginTransaction();
    
    $this->info('extracting salesmtd...');
    try {
      $r = $this->backlogSalesmtdChangeItem($br->id, $f, $t, $this);
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }
    
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

 

  public function backlogSalesmtdChangeItem($branchid, $from, $to, $c) {
    try {
      return $this->posUploadRepo->backlogSalesmtdChangeItem($branchid, $from, $to, $c);
    } catch(Exception $e) {
      throw $e;    
    }
  }

 

  




}