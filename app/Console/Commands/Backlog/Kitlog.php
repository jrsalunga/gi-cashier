<?php namespace App\Console\Commands\Backlog;

use App\Events\Process\AggregatorKitlog;
use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use Illuminate\Console\Command;
use App\Services\KitlogImporter;
use App\Repositories\DailySales2Repository as DS;

class Kitlog extends Command {

	protected $signature = 'backlog:kitlog {date : YYYY-MM-DD} {--brcode=ALL : Branch Code} {--dateTo=NULL : YYYY-MM-DD} {--eom=false : Run EOM dataset generator only}';
  protected $description = 'backlog process for kitchen log for a given date range run via command.';

  protected $extractor;
  protected $kitlog;
  protected $ds;
  protected $date;
  protected $dateTo;

  public function __construct(BackupExtractor $extractor, KitlogImporter $kitlog, DS $ds) {
    parent::__construct();
    
    $this->extractor = $extractor;
    $this->kitlog = $kitlog;
    $this->ds = $ds;
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

        // if (!$this->option('eom')===true) { // check to run EOM, dataset generator

          if ($this->extract($b->code, $d, true)==1) {

            $res = $this->kitlog->import($b->id, $d, $this->extractor->getExtractedPath(), $this);
            
            $this->clean();
            
            $ctr++;
            $this->ds->firstOrNewField(['kitlog'=>1, 'branchid'=>$b->id, 'date'=>$d->format('Y-m-d')], ['branchid', 'date']);
          } // endif: extract
        // }


        if ($res>0) {
          $this->line('AggregatorKitlog - day_kitlog_food');
          event(new AggregatorKitlog('day_kitlog_food', $d, $b->id));
          $this->line('AggregatorKitlog - month_kitlog_food');
          event(new AggregatorKitlog('month_kitlog_food', $d, $b->id));
          $this->line('AggregatorKitlog - day_kitlog_area');
          event(new AggregatorKitlog('day_kitlog_area', $d, $b->id));
          $this->line('AggregatorKitlog - month_kitlog_area');
          event(new AggregatorKitlog('month_kitlog_area', $d, $b->id));
        }

        if ($d->copy()->endOfMonth()->format('Y-m-d') == $d->format('Y-m-d')) {
          $this->line($b->code.' EOM: trigger DailySalesSuccess2');
          event(new \App\Events\Backup\DailySalesSuccess2($d, $b->id)); // recompute Monthlysales
          event(new \App\Events\Process\AggregatorKitlog('dataset_area', $d, $b->id));
          event(new \App\Events\Process\AggregatorKitlog('dataset_food', $d, $b->id));
          event(new \App\Events\Process\AggregatorKitlog('dataset_area', $d, NULL));
          event(new \App\Events\Process\AggregatorKitlog('dataset_food', $d, NULL));
        }
      }
      $this->line('******************');
      $this->line($ctr.' - '.$res);
      $this->line('******************');
      

      $c++;
    } while ($c <= $count); 

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
      event(new Notifier('Backlog\KitchenLog: '.$msg));
    else
      $this->error($msg);
  }

  
}