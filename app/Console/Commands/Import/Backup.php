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

class Backup extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'import:backup {--reset= : reset}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Import backlog month of a branch on CRON';

  protected $process;

  protected $dr;

  protected $type = 11;

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

    $this->info('Running: import:backup');
    
    $locator = new Locator('pos');

    $this->info(app()->environment());
    if (app()->environment()=='production') {
      $folder = '/home/server-admin/Public/maindepot/TEST_POS_BACKUP';
      $delimiter = '/';
      $offset = 9;
      $offset2 = 6;
    } else {
      $folder = 'TEST_POS_BACKUP';
      $delimiter = '\\';
      $offset = 8;
      $offset2 = 5;
    }
        
    $fs = $locator->allFiles($folder);

      // dd($locator->realFullPath('..'));

    // $fs = $locator->allFiles('TEST_POS_BACKUP');
    // $fs = $locator->allFiles();

    // print_r($fs);

    $td = NULL;
    $skip = false;
      
    // foreach($fs as $k => $v) {
    //   $this->info($v);
    // }

    // exit;

    foreach($fs as $k => $v) {

      if (ends_with($v, '.ZIP')) {

      
        $f = explode($delimiter,$v);
        // $this->info($d->format('Y-m-d'));

        try {
          $d = filename_to_date2($f[$offset]);
        } catch(Exception $e) {
          continue;
        }
        

        if (is_null($td))
          $td=$d->copy()->lastOfMonth();

        // if ($td->format('m')==$d->format('m')) {
        if (($td->format('m')==$d->format('m')) && ($td->format('Y')==$d->format('Y')) && !$skip) {

          $this->info($k.' L1 '.$f[$offset2].' '.$td->format('Y-m-d').'=='.$d->format('Y-m-d'));
          
          if ($td->format('Y-m-d')==$d->format('Y-m-d'))
            $skip = true;

          $temp = $d;
        } else {
        
          // $this->info($k.' '.$td->format('Y-m-d').'=='.$temp->format('Y-m-d').'  temp');
          if ($td->eq($temp)) {
            $this->info($f[$offset2].' is eom! '. $temp->format('Y-m-d'));
          }
          else 
            $this->info($f[$offset2].' not eom! '. $temp->format('Y-m-d'));


    
          // $this->info($k.' '.$td->format('Y-m-d').'=='.$d->format('Y-m-d'));
          $td=$d->copy()->lastOfMonth();
          $temp = $d;
          $this->info($k.' L2 '.$f[$offset2].' '.$td->format('Y-m-d').'=='.$d->format('Y-m-d'));
        }
        $skip = false;
        
      } else {
        $this->info($v);
      }

      // $this->info($k.' '.$f[5].'  '.$f[6].'  '.$f[7].'  '.$td.'  '.$f[8]);
    }



    $this->info('End running: import:backup');
    exit;
   
    

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
        $this->error('backup do not exist');
      }
      
    }

    
    


    
    exit;
  }



   
}
