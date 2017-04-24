<?php namespace App\Console\Commands\Backlog;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use Illuminate\Contracts\Mail\Mailer;

class SalesProcessMatcher extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ds:process';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process dailysales backlog';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $sales;
  protected $ds;
  protected $posUploadRepo;

  public function __construct(DSRepo $ds, SalesRepo $sales, PosUploadRepo $posUploadRepo, Mailer $mailer)
  {
    parent::__construct();
    $this->sales = $sales;
    $this->ds = $ds;
    $this->posUploadRepo = $posUploadRepo;
    $this->mailer = $mailer;
  }

  public function handle() {

    // 5 * * * * root /usr/bin/php /var/www/html/gi-cashier/artisan ds:process && curl -sm 30 k.wdt.io/giligans.app@gmail.com/ds-process?c=5_*_*_*_*

    //DB::enableQueryLog();

    $this->info(' starting...');

    $proc = Process::where('filedate', '>=', '2016-12-01')
                ->where('processed', 0)
                ->orderBy('code')
                ->orderBy('filedate')
                ->first();


    if ($proc) {

      $d = backup_to_carbon_date($proc->filename);
      $backup = 'GC'.$proc->filedate->format('mdy').'.ZIP';

      $br = Branch::where('code', $proc->code)->first();
      if (!$br) {
        $msg = 'Invalid Branch Code.';
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        exit;
      }

      
      $locator = new Locator('pos');
      if (!$locator->exists($proc->path)) {
        $msg = 'Backup '.$proc->path.' do not exist.';
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        exit;
      }


      if ($proc->filedate->gt($d) || $proc->filedate->format('Y-m')!=$d->format('Y-m')) {
        $msg = 'No data for the date of '.$$proc->filedate->format('Y-m-d').' on '.$proc->filename.' backup';
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        exit;
      } 


      $bckup = $this->posUploadRepo->findWhere(['branchid'=>$br->id, 'filename'=>$backup])->first();
      if (!$bckup) {
        $msg = 'No record found on backup table.'; 
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        exit;
      }

      if (!$this->extract($locator->realFullPath($proc->path), $proc->code)) {
        $msg = 'Unable to extract '. $proc->filename .', the backup maybe corrupted. Try to generate another backup file and try to re-upload.';
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        exit;
      }
      
      

      $this->info(' start processing '. $proc->code ."'s ".$proc->filedate->format('Y-m-d').' on '. $proc->filename);

      DB::beginTransaction();

      $this->info(' extracting purchased...');
      try {
        $this->processPurchased($proc->filedate, $bckup);
      } catch (Exception $e) {
        $msg = $e->getMessage();
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        $this->removeExtratedDir();
        DB::rollback();
        exit;
      }

      $this->info(' extracting salesmtd...');
      try {
        $this->processSalesmtd($proc->filedate, $bckup);
      } catch (Exception $e) {
        $msg = $e->getMessage();
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        $this->removeExtratedDir();
        DB::rollback();
        exit;
      }

      $this->info(' extracting charges...');
      try {
        $this->processCharges($proc->filedate, $bckup);
      } catch (Exception $e) {
        $msg = $e->getMessage();
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        $this->removeExtratedDir();
        DB::rollback();
        exit;
      }

      $this->info(' updating manpower...');
      try {
        $this->updateManpower($proc->filedate, $bckup);
      } catch (Exception $e) {
        $msg = $e->getMessage();
        $proc->note = $msg;
        $proc->processed = 2;
        $proc->save();
        $this->info($msg);
        $this->removeExtratedDir();
        DB::rollback();
        exit;
      }

      //DB::rollback();
      DB::commit();
        
      

      $proc->note = 'success';
      $proc->processed = 1;
      $proc->save();  
      $this->removeExtratedDir();
      $this->info(' done');


      $data = [
        'user'      => 'root',
        'cashier'   => 'bot',
        'filename'  => $proc->filename,
        'subject'   => $proc->code ."'s ".$proc->filedate->format('Y-m-d').' on '. $proc->filename
      ];

      $this->mailer->queue('emails.backup-processsuccess', $data, function ($message) use ($data){
        $message->subject($data['subject']);
        $message->from('server-admin@server01');
        $message->to('giligans.app@gmail.com');
      });

      exit;




    } else {
      $this->info('no record found...');
    }


    
    
   //$this->info(print_r(DB::getQueryLog()));


  }

  private function extract($filepath, $brcode) {
    return $this->posUploadRepo->extract($filepath, 'admate', false, $brcode);  
  }

  private function removeExtratedDir() {
    return $this->posUploadRepo->removeExtratedDir();
  }

  public function processPurchased($date, $backup){
    try {
      $this->posUploadRepo->postPurchased2($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processSalesmtd($date, $backup){
    try {
      $this->posUploadRepo->postSalesmtd($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function processCharges($date, $backup){
    try {
      $this->posUploadRepo->postCharges($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function updateManpower($date, $backup){
    try {
      $this->posUploadRepo->updateDailySalesManpower($date, $backup);
    } catch(Exception $e) {
      throw $e;    
    }
  }
}