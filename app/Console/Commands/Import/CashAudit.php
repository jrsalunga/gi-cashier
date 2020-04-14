<?php namespace App\Console\Commands\Import;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use Illuminate\Console\Command;

class CashAudit extends Command
{

  /*
  table: boss.for_process.type=7
  */

	protected $signature = 'import:cash-audit {date : YYYY-MM-DD} {--brcode=ALL : Branch Code} {--dateTo=NULL : YYYY-MM-DD}';
  protected $description = 'extract backup and import to for_process table and to processed per month';

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
    // $count = $this->dateTo->diffInDays($this->date);
    $count = $this->dateTo->diffInMonths($this->date);

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
        
    // $this->line('diff: '.$count);
    $c = 0;
    foreach ($br as $key => $b) {
      $this->line($b->code);
      $c = 0;      
      do {

        $d = $this->date->copy()->addMonths($c)->endOfMonth();
        $this->line($d->format('Y-m-d'));

        $ctr = 0;
        
        $del = Process::where('type', 7)->where('filedate', $d->format('Y-m-d'))->where('code', $b->code)->delete();
        $this->line('delete: '.$del);

        // if ($this->extract($b->code, $d, true)==1) {
          Process::firstOrCreate([
            'filename'  => 'GC'.$d->format('mdy').'.ZIP',
            'filedate'  => $d->format('Y-m-d'),
            'code'      => $b->code,
            'path'      => $b->code.DS.$d->format('Y').DS.$d->format('m').DS.'GC'.$d->format('mdy').'.ZIP',
            'type'      => 7,
            'processed' => 0,
            'note'      => 'import:cash-audit '.stl($b->code).' '.$d->format('Y-m-d'),
          ]);
          $ctr++;
        // }
        // $this->line('******************');
        $this->line('insert: '.$ctr);
        $this->line('*************');

        $c++;
      } while ($c <= $count); 
      // $this->line(' ');
    } // end: for $br
    exit;
  }

  public function extract($brcode, $date, $show=true) {

    // $this->info('has_backup: '.$this->extractor->has_backup($brcode, $date->format('Y-m-d')));
    // $this->info($this->extractor->getFilePath());   

    $this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate');
    // $this->info('extract: '.$this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate'));   
    
    $file = 'CSH_AUDT.DBF';
    if (file_exists($this->extractor->getExtractedPath().DS.$file)) {
      // $this->info($this->extractor->getExtractedPath()); 
      if ($show)
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
      event(new Notifier('Import\CashAudit: '.$msg));
    else
      $this->error($msg);
  }

  
}