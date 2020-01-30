<?php namespace App\Console\Commands\Backlog;

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

      foreach ($br as $key => $b) {

        $this->extract($b->code, $d, false);
      }
      $this->line('******************');
      $this->line(' ');

      $c++;
    } while ($c <= $count); 

    exit;

  }

  public function extract($brcode, $date, $show=true) {

    // $this->info('has_backup: '.$this->extractor->has_backup($brcode, $date->format('Y-m-d')));

    // $this->info($this->extractor->getFilePath());   

    $this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate');
    // $this->info('extract: '.$this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate'));   
    
    $file = $date->format('Ymd').'.LOG';
    // $this->info($this->extractor->getExtractedPath()); 
    if (file_exists($this->extractor->getExtractedPath().DS.$file))
      $this->info($brcode.' - '.$file);
    else
      if ($show)
        $this->error($brcode);

    $this->extractor->clean();   
    // $this->info('clean: '.$this->extractor->clean());   
    
  }
  
  private function notify($msg) {
  	if(app()->environment()=='production')
      event(new Notifier('Cron\BacklogCharges: '.$msg));
    else
      $this->error($msg);
  }

  
}