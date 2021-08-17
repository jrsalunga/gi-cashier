<?php namespace App\Console\Commands\Process;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use Illuminate\Console\Command;
use App\Services\BegBalImporter;

class BegBal extends Command {

  protected $signature = 'process:begbal {date : YYYY-MM-DD} {--brcode=ALL : Branch Code} {--dateTo=NULL : YYYY-MM-DD}';
  protected $description = 'process begbal for a given date range run via command.';

  protected $extractor;
  protected $importer;
  protected $date;
  protected $dateTo;
  protected $file = 'BEG_BAL.DBF';

  public function __construct(BackupExtractor $extractor, BegBalImporter $importer) {
    parent::__construct();
    
    $this->extractor = $extractor;
    $this->importer = $importer;
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
    $this->date = $date->startOfMonth();  

    $dateTo = is_iso_date($this->option('dateTo')) ? Carbon::parse($this->option('dateTo')) : $date;
    $this->dateTo =  $dateTo->gt($date) && $dateTo->lte(c()) ? $dateTo : $date;
    $count = $this->dateTo->diffInMonths($this->date);

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

      $d = $this->date->copy()->startOfMonth()->addMOnths($c);

      $ctr = $res = 0;
      foreach ($br as $key => $b) {

        if ($this->extractor->has_backup($b->code, $d)) {

          $this->line($b->code.' Extractor');

          if ($this->extract($b->code, $d, true)==1) {

            $res = $this->importer->import($b->id, $d, $this->extractor->getExtractedPath(), $this);
            
            $this->clean();
          } // endif: extract


          event(new \App\Events\Process\AggregatorDaily('begbal', $d, $b->id));  // update ds
          event(new \App\Events\Process\AggregatorMonthly('begbal', $d, $b->id));  // update ms_expense

       
        } else {
          $this->line($b->code.' '.$d->format('Y-m-d').' no backup');
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
    
    if (file_exists($this->extractor->getExtractedPath().DS.$this->file)) {
      $this->info($brcode.' - '.$this->file);
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
      event(new Notifier('Process\BegBal: '.$msg));
    else
      $this->error($msg);
  }

  
}