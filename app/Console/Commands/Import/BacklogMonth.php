<?php namespace App\Console\Commands\Import;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use App\Models\Process;
use App\Repositories\DateRange;
use App\Events\Notifier;

class BacklogMonth extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'import:backlog-month {from : from date} {to : to date} {brcode : Branch Code} {--reset= : reset}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Import backlog month';

  protected $process;

  protected $dr;

  protected $type = 3;

  public function __construct(Process $process, DateRange $dr)
  {
    parent::__construct();
    $this->process = $process;
    $this->dr = $dr;
  }
  



  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
   
    $br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
    if (!$br) {
      $this->error('Invalid Branch Code.');
      exit;
    }
    

    if (!is_iso_date($this->argument('from'))) {
      $this->error('from is invalid date format');
      exit;
    }

    if (!is_iso_date($this->argument('to'))) {
      $this->error('to is invalid date format');
      exit;
    }

    $from = Carbon::parse($this->argument('from'));
    $to = Carbon::parse($this->argument('to'));

    if ($from->gt($to)) {
      $this->error('invalid date range: --from is greater than --to');
      exit;
    }

    $reset = is_null($this->option('reset')) 
      ? false
      : $this->option('reset')=='true' ? true:false;

    $this->info('reset: '.$reset);

    $this->dr->fr = $from;
    $this->dr->to = $to;

    foreach ($this->dr->monthInterval() as $key => $date) {

      $eom = $date->copy()->endOfMonth();
      $this->line($eom->format('Y-m-d'));
      
      $locator = new Locator('pos');
      $backup = 'GC'.$eom->format('mdy').'.ZIP';
      $path = $br->code.DS.$eom->format('Y').DS.$eom->format('m').DS.$backup;
      
      //$this->info($locator->realFullPath($path));
      if ($locator->exists($path)) {

        $attr = [
          'filename'  => $backup,
          'filedate'  => $eom->format('Y-m-d'),
          'code'      => $br->code,
          'path'      => $path,
          'type'      => $this->type
        ];
        
        $p = $this->process->firstOrCreate($attr);
        
        if ($p) {
          $this->line('loaded');
          if ($reset) {
            $p->processed = 0; 
            $p->save();
          }
        } else
          $this->error('error on inserting processed');
      } else {
        if(app()->environment()=='production')
          event(new Notifier(session('Import\BacklogMonth: Backup do not exist. ('.$path.')')));
        else
          $this->error('backup do not exist');
      }
      
    }

    
    


    
    exit;
  }



   
}
