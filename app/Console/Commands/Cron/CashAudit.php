<?php namespace App\Console\Commands\Cron;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use App\Services\CashAuditImporter;

class CashAudit extends Command
{

	protected $signature = 'cron:cash-audit';
  protected $description = 'process the cash audit log daily based on EOM date via cron';
  protected $process;
  protected $cashAudit;
  protected $ds;

  public function __construct(BackupExtractor $extractor, Process $process, CashAuditImporter $cashAudit) {
    parent::__construct();
    $this->process = $process;
    $this->cashAudit = $cashAudit;
    $this->extractor = $extractor;
  }

  public function handle() {

    $process = $this->process
                    ->where('processed', '0')
                    ->where('type', '7')
                    ->orderBy('code')
                    ->orderBy('filedate')
                    ->first();

    if (!is_null($process)) {

	    if(app()->environment()=='local')
	    	$this->info('start processing cron Cash Audit...');
	    
    	$br = Branch::where('code', strtoupper($process->code))->first();
  		if (!$br) {
  			$this->notify('Branch not found.');
  			$process->processed = 3;
  			$process->save();
	      exit;
	  	}

      // $this->line($process->filedate);

	    // set to know the backup is on process
  	  $process->processed = 2;
    	$process->save();

      $fr = $process->filedate->copy()->startOfMonth();
      $to = $process->filedate->copy()->endOfMonth();

      $cmd = app()->environment()=='local' ? $this : NULL;
      if ($this->extract($br->code, $to, true)==1) {
  	   
        DB::beginTransaction();

        $res = 0;

        if(!is_null($cmd))
          $this->info('importing Cash Audit via cron...');
        
        try {
          $res = $this->cashAudit->importByDr($br->id, $fr, $to, $this->extractor->getExtractedPath(), $cmd);
        } catch (Exception $e) {
          $this->notify('cron:cashAudit:'.$e->getMessage());
          $this->clean();
          DB::rollback();
          $process->processed = 3;
          $process->save();
          exit;
        }

        if(!is_null($cmd))
          $this->info('removing directory: '.$this->extractor->getExtractedPath());
        $this->clean();
            
        DB::commit();
        
        $this->line('******************');
        $this->line($res);
        $this->line('******************');

    	  $process->processed = 1;
    	  $process->save();

      } else {
        if(!is_null($cmd))
          $this->info('No backup found');

        $process->processed = 4; // no backup found
        $process->save();
      }
    } else {
      if(app()->environment()=='local')
        $this->info('no more process');
    } // end: !is_null($process)
  }

  public function extract($brcode, $date, $show=true) {

    $this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate');
    
    $file = 'CSH_AUDT.DBF';
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
      event(new Notifier('Cron\CashAudit: '.$msg));
    else
      $this->error($msg);
  }
}