<?php namespace App\Console\Commands\Checker;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use Illuminate\Console\Command;

class KitchenLog extends Command
{
  /*
  *   Check backup if it has kitchen log.
  */
  protected $signature = 'checker:kitchen-log {date : YYYY-MM-DD} {--brcode=ALL : Branch Code} {--dateTo=NULL : YYYY-MM-DD}';
  protected $description = 'check whether there is a kitchen log on a backup.';

  protected $extractor;
  protected $date;
  protected $dateTo;

  public function __construct(BackupExtractor $extractor) {
    parent::__construct();
    
    $this->extractor = $extractor;
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

    $this->comment('Date(s): '.$this->date->format('Y-m-d').' - '.$this->dateTo->format('Y-m-d').' ('.$count.')');

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

      //$del = Process::where('type', 6)->where('filedate', $d->format('Y-m-d'))->delete();
      //$this->line('for_process deleted: '.$del);

      $ctr = 0;
      foreach ($br as $key => $b) {

        if ($this->extract($b->code, $d, true)==1) {
          // Process::firstOrCreate([
          //   'filename'  => 'GC'.$d->format('mdy').'.ZIP',
          //   'filedate'  => $d->format('Y-m-d'),
          //   'code'      => $b->code,
          //   'path'      => $b->code.DS.$d->format('Y').DS.$d->format('m').DS.'GC'.$d->format('mdy').'.ZIP',
          //   'type'      => 6,
          //   'processed' => 3,
          //   'note'      => 'import:kitchen-log '.stl($b->code).' '.$d->format('Y-m-d'),
          // ]);
          $ctr++;
        }
      }
      $this->line('******************');
      $this->line($ctr);
      $this->line('******************');

      $c++;
    } while ($c <= $count); 

    exit;

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
    $this->extractor->clean();   
    return 1;    
  }
  
  private function notify($msg) {
    if(app()->environment()=='production')
      event(new Notifier('Checker\KitchenLog: '.$msg));
    else
      $this->error($msg);
  }

  
}