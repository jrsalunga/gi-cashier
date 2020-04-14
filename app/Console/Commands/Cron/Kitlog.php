<?php namespace App\Console\Commands\Cron;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Helpers\BackupExtractor;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Events\Notifier;
use App\Services\KitlogImporter;
use App\Events\Process\AggregatorKitlog;

class Kitlog extends Command
{

	protected $signature = 'cron:kitlog';
  protected $description = 'process the kitchen log daily via cron';
  protected $process;
  protected $kitlog;
  protected $ds;

  public function __construct(BackupExtractor $extractor, Process $process, DSRepo $ds, KitlogImporter $kitlog) {
    parent::__construct();
    $this->process = $process;
    $this->kitlog = $kitlog;
    $this->ds = $ds;
    $this->extractor = $extractor;
  }

  public function handle() {

    $process = $this->process
                    ->where('processed', '0')
                    ->where('type', '6')
                    ->orderBy('code')
                    ->orderBy('filedate')
                    ->first();

    if (!is_null($process)) {

	    if(app()->environment()=='local')
	    	$this->info('start processing cron kitlog...');
	    
    	$br = Branch::where('code', strtoupper($process->code))->first();
  		if (!$br) {
  			$this->notify('Branch not found.');
  			$process->processed = 3;
  			$process->save();
	      exit;
	  	}

      $this->line($process->filedate);


	  // set to know the backup is on process
	  $process->processed = 2;
  	$process->save();


	  DB::beginTransaction();


    if ($this->extract($br->code, $process->filedate, true)==1) {
      
      $cmd = app()->environment()=='local' ? $this : NULL;

      $this->info('extracting kitlog via cron...');
      try {
        $res = $this->kitlog->import($br->id, $process->filedate, $this->extractor->getExtractedPath(), $cmd);
      } catch (Exception $e) {
        $this->notify('cron:kitlog:'.$e->getMessage());
        $this->clean();
        DB::rollback();
        $process->processed = 3;
    		$process->save();
        exit;
      }
      
      if($res>0) {
        $this->ds->firstOrNewField(['kitlog'=>1, 'branchid'=>$br->id, 'date'=>$process->filedate->format('Y-m-d')], ['branchid', 'date']);

        event(new AggregatorKitlog('day_kitlog_food', $process->filedate, $br->id));
        event(new AggregatorKitlog('month_kitlog_food', $process->filedate, $br->id));
        event(new AggregatorKitlog('day_kitlog_area', $process->filedate, $br->id));
        event(new AggregatorKitlog('month_kitlog_area', $process->filedate, $br->id));

        if ($process->filedate->copy()->endOfMonth()->format('Y-m-d') == $process->filedate->format('Y-m-d'))
          event(new \App\Events\Backup\DailySalesSuccess2($process->filedate, $br->id)); // recompute Monthlysales
      }
      
      if(app()->environment()=='local')
	    	$this->info('removing directory...');
      
      $this->clean();
      

      if(app()->environment()=='local') {
        $this->line('******************');
        $this->line($res);
        $this->line('******************');
      }

      $process->processed = 1;
      $process->save();

    } else {
      if(!is_null($cmd))
        $this->info('No backup found');

      $process->processed = 4; // no backup found
      $process->save();
    }

    DB::commit();

    } else {
      if(app()->environment()=='local')
        $this->info('no more process');
    } // end: !is_null($process)
  }

  public function extract($brcode, $date, $show=true) {

    $this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate');
    
    $file = $date->format('Ymd').'.LOG';
    if (file_exists($this->extractor->getExtractedPath().DS.$file)) {
      $this->info($brcode.' - '.$file);
    } else {
      if ($show)
        if ($this->extractor->has_backup($brcode, $date)==0)
          $this->question($brcode);
        else
          $this->error($brcode);
      return 0;
    }
    return 1;    
  }

  public function clean() {
    return $this->extractor->clean();
  }
  
  private function notify($msg) {
  	if(app()->environment()=='production')
      event(new Notifier('Cron\Kitlog: '.$msg));
    else
      $this->error($msg);
  }
}