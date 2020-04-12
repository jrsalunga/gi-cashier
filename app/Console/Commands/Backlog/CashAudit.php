<?php namespace App\Console\Commands\Backlog;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use Illuminate\Console\Command;
use App\Services\CashAuditImporter;

class CashAudit extends Command
{

	protected $signature = 'backlog:cash-audit {date : YYYY-MM-DD} {--brcode=ALL : Branch Code} {--dateTo=NULL : YYYY-MM-DD}';
  protected $description = 'backlog process for cash audit dbf.';

  protected $extractor;
  protected $cashAudit;
  protected $date;
  protected $dateTo;

  public function __construct(BackupExtractor $extractor, CashAuditImporter $cashAudit) {
    parent::__construct();
    
    $this->extractor = $extractor;
    $this->cashAudit = $cashAudit;
  }

  public function handle() {

    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->error('Invalid date.');
      exit;
    }

    $date = Carbon::parse($date);
    if ($date->gte(c())) {
      $this->error('Invalid backup date. Too advance to backup.');
      exit;
    } 
    $this->date = $date;  

    $dateTo = is_iso_date($this->option('dateTo')) ? Carbon::parse($this->option('dateTo')) : $date;
    $this->dateTo =  $dateTo->gt($date) && $dateTo->lte(c()) ? $dateTo : $date;
    $count = $this->dateTo->diffInDays($this->date);

    $this->comment('Date(s): '.$this->date->format('Y-m-d').' - '.$this->dateTo->format('Y-m-d').' ('.($count+1).')');

    if (strtoupper($this->option('brcode'))==='ALL')
      $br = Branch::orderBy('code')->get(['code', 'descriptor', 'id']);
    else {
      $br = Branch::where('code', strtoupper($this->option('brcode')))->get(['code', 'descriptor', 'id']);
      if (count($br)<=0) {
        $this->info('Invalid Branch Code.');
        exit;
      }
    }

    $c = 0;
    do {

      $d = $this->date->copy()->addDays($c);
      $this->line($d->format('Y-m-d'));

      $ctr = $res = 0;
      foreach ($br as $key => $b) {

        if ($this->extract($b->code, $d, true)==1) {

          $res = $this->cashAudit->import($b->id, $d, $this->extractor->getExtractedPath(), $this);
          
          $this->clean();
          
          $ctr++;
        } // endif: extract
      }
      $this->line('******************');
      $this->line($ctr.' - '.$res);
      $this->line('******************');

      // event(new \App\Events\Process\AggregatorKitlog('day_kitlog_food', $d, $b->id));
      // event(new \App\Events\Process\AggregatorKitlog('month_kitlog_food', $d, $b->id));
      // event(new \App\Events\Process\AggregatorKitlog('day_kitlog_area', $d, $b->id));
      // event(new \App\Events\Process\AggregatorKitlog('month_kitlog_area', $d, $b->id));

      $c++;
    } while ($c <= $count); 

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
      event(new Notifier('Backlog\CashAudit: '.$msg));
    else
      $this->error($msg);
  }

  
}