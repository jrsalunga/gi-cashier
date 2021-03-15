<?php namespace App\Console\Commands\Cron;

use File;
use DB;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use App\Services\PurchaseImporter as Importer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


class Purchase extends Command
{

	protected $signature = 'cron:purchase';
  protected $description = 'process the updated backup files from the STAGING folder via cron';
  protected $importer;
  protected $filepath = NULL;
  protected $root_path = NULL;

  public function __construct(BackupExtractor $extractor, Importer $importer) {
    parent::__construct();
    $this->importer = $importer;
    $this->extractor = $extractor;
    $this->root_path = storage_path();
  }

  public function handle() {

    $cmd = app()->environment()=='local' ? $this : NULL;
    $factory = new Locator('backup_factory');
    $factory_path = config('gi-dtr.upload_path.backup_factory.'.app()->environment());

    // check all files from the staging directory. \POS_BACKUP_FACTORY\STAGING
    $files = $factory->allFiles($factory_path.DS.'STAGING');

    if (!is_null($cmd))
      $this->info('extracting PURCHASE.DBF via cron...');

    // check if there is a backup file on staging folder to process.
    if (count($files)>0) {

      foreach ($files as $file) {
        if (ends_with($file, '.ZIP')) {
      
          if (!is_null($cmd))
            $this->info($files[0]);

          $this->filepath = $file;
          $boom = explode('\\', $file);
          $cnt = count($boom);
          $filename = $boom[($cnt-1)];
          $brcode = $boom[($cnt-2)];
          $date = filename_to_date2($filename);


          $br = Branch::where('code', strtoupper($brcode))->first();
          if (!$br) {
            if (!is_null($cmd))
              $this->error('Branch not found.');
            exit;
          }

          
          if (!is_null($cmd))
            $this->info($date);


          if ($this->extract($brcode, $date)==1) {
            DB::beginTransaction();

            // $this->info($this->extracted_path);

            try {
              $res = $this->importer->import($br->id, $date, $this->extracted_path, $cmd);
            } catch (Exception $e) {
              $this->info('cron:purchase:error: '.$e->getMessage());
              $this->clean();
              DB::rollback();
              exit;
            }

            if (!is_null($cmd)) 
              $this->info('this.transaction='. $res);



            // push emp meal on purchase
            event('transfer.empmeal', ['data'=>['branch_id'=> $br->id, 'date'=>$date, 'suppliercode'=>$br->code]]);
            //$this->logAction('success:process:purchased', $log_msg.$msg);

            // compute delivery fee (GrabFood, Food Panda)   \\App\Listeners\BackupEventListener
            event('deliveryfee', ['data'=>['branch_id'=> $br->id, 'date'=>$date]]);


            event(new \App\Events\Process\AggregateComponentDaily($date, $br->id)); // recompute Daily Component
            event(new \App\Events\Process\AggregateDailyExpense($date, $br->id)); // recompute Daily Expense
            event(new \App\Events\Process\AggregateComponentMonthly($date, $br->id)); // recompute Monthly Component
            event(new \App\Events\Process\AggregateMonthlyExpense($date, $br->id)); // recompute Monthly Expense
            event(new \App\Events\Process\AggregatorMonthly('trans-expense', $date, $br->id));
            event(new \App\Events\Process\AggregatorMonthly('cash_audit', $date, $br->id));

            

            DB::commit();


            $src = $factory_path.'STAGING'.DS.$br->code.DS.$filename;
            $dir = $factory_path.'PROCESSED'.DS.'BACKUP'.DS.$br->code;
            $dest = $dir.DS.$filename;

            if (!is_dir($dir))
              mkdir($dir, 777, true);

            if (!is_null($cmd)) 
              $this->info($src);

            try {
              if (app()->environment()=='local')
                File::copy($src, $dest);
              else
                File::move($src, $dest);
            } catch(Exception $e){
              throw new Exception("Error ". $e->getMessage());    
            }
          
          }
          $this->clean();
          exit;
          } // end: ==='GC && ZIP'
        } // end: foreach(files)
       
        if (!is_null($cmd))
          $this->info('No BACKUP found.');
    } // end: count($files)
    // no files found
  }

  public function extract($brcode, $date, $pwd='admate', $show=true) {

    $zip = new ZipArchive();  
    $zip_status = $zip->open($this->filepath);

    if($zip_status === true) {

      if(!is_null($pwd))
        $zip->setPassword($pwd);

      $path = $this->root_path.DS.'backup'.DS.$brcode.DS.pathinfo($this->filepath, PATHINFO_FILENAME);

      if(is_dir($path)) {
        $this->removeDir($path);
      }
      mkdir($path, 0777, true);

      $this->extracted_path = $path;

      if(!$zip->extractTo($path)) {
        $zip->close();
        return false;
      }
      $this->extracting = true;

      $zip->close();

      return 1;
    }
  }

  public function extracts($brcode, $date, $show=true) {

    $this->info($this->extractor->getRootPath());

    $this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate');
    
    $file = 'PURCHASE.DBF.DBF';
    if (file_exists($this->extractor->getExtractedPath().DS.$file)) {
      if ($show)
        $this->info($brcode.' - '.$file);
    } else {
      if ($show)
        if ($this->extractor->has_backup($brcode, $date)==0)
          $this->question($brcode);
        else
          $this->error($brcode);


        $this->error('no extracted');
      return 0;
    }
    return 1;    
  }

  public function clean() {
    return $this->removeDir($this->extracted_path);
    return $this->extractor->clean();
  }
  
  private function notify($msg) {
  	if(app()->environment()=='production')
      event(new Notifier('Cron\Purchase: '.$msg));
    else
      $this->error($msg);
  }

  public function removeDir($dir){
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
      if ($file->isDir()){
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }
    if (rmdir($dir)) {
      $this->extracting = false;
      $this->extracted_path = NULL;
      return true;
    } else
      return false;
    
    
  }
}