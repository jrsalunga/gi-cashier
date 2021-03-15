<?php namespace App\Console\Commands\Cron;

use File;
use DB;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Events\Notifier;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use App\Helpers\BossBranch;


class PurchaseNew extends Command
{

	protected $signature = 'cron:purchase-new';
  protected $description = 'process the updated backup files from the STAGING folder via cron';
  protected $filepath = NULL;
  protected $root_path = NULL;
  protected $bossBranch;  
  protected $fileStorage;  

  public function __construct(BossBranch $bossBranch) {
    parent::__construct();
    $this->bossBranch = $bossBranch;
    $this->fileStorage = app()->fileStorage;
    $this->root_path = storage_path();
  }

  public function handle() {

    $cmd = app()->environment()=='local' ? $this : NULL;
    $factory = new Locator('backup_factory');
    $factory_path = config('gi-dtr.upload_path.backup_factory.'.app()->environment());

    // check all files from the staging directory. \POS_BACKUP_FACTORY\STAGING
    $files = $factory->allFiles($factory_path.DS.'STAGING');
    $processed = $factory->allFiles($factory_path.DS.'PROCESSED');
        
    if (!is_null($cmd))
      $this->info('checking STAGING...');

    
    // $this->info(print_r($files));
    
    // check if there is a backup file on staging folder to process.
    if (count($files)>0) {
      
      foreach ($files as $idx => $file) {
        if (ends_with($file, '.NEW')) {
          $this->info(json_encode($file));

          // if (!is_null($cmd))
            $this->info($file); 

          $this->filepath = $file;

           // $this->info('BEFORE DS');
          $boom = explode(DS, $file);
           // $this->info('AFTER DS');
          if (!is_null($cmd))
            $this->info(json_encode($boom));
          $cnt = count($boom);
          $filename = $boom[($cnt-1)];
          $brcode = $boom[($cnt-2)];
          $date = Carbon::now();



          if (strtoupper($filename)==='PURCHASE.NEW') {

            $br = Branch::where('code', strtoupper($brcode))->first();
            if (!$br) {
              if (!is_null($cmd))
                $this->error('Branch not found.');
              exit;
            }

            $apd_dir = 'APN'.DS.$date->format('Y').DS.$brcode.DS.$date->format('m').DS.$date->format('d');

            // copy to processed
            $dir = $factory_path.'PROCESSED'.DS.$apd_dir;
            $destp = $dir.DS.$filename;

            if (!is_dir($dir))
              mkdir($dir, 777, true);
           
           // $this->info('Before File Copy');

            try {
              File::copy($this->filepath, $destp);
            } catch(Exception $e){
              throw new Exception("Error copy to PROCESSED. ". $e->getMessage());    
            }

           // $this->info('Before move to APD');

            // move to APD 
            $dest = $this->fileStorage->realFullPath($apd_dir);
            $apd_filepath = $dest.DS.$filename;

            if (!is_dir($dest))
              mkdir($dest, 75, true);

            try {
              if (app()->environment()=='local')
                File::copy($this->filepath, $apd_filepath);
              else   
                File::copy($this->filepath, $apd_filepath);
                // File::move($this->filepath, $apd_filepath);
            } catch(Exception $e){
              throw new Exception("Error move to APD. ". $e->getMessage());    
            }


            // $this->info('Before sendEmail');
            $this->sendEmail($br, $date, $apd_filepath);
            // $this->info('after sendEmail');


            // $this->info('Before test_log');
            test_log($date->format('Y-m-d').','.$br->code, $factory_path.DS.'STAGING'.DS.$date->format('Y').'-purchase.new.log');


            exit;
          } // end: ==='PURCHASE.NEW'
        } // end: ends_with($file)
        if (!is_null($cmd))
        $this->info($idx.'. No PURCHASE.NEW found.');
      
      } // end: foreach(files)
    } // end: count($files)
  }

  private function sendEmail(Branch $branch, Carbon $date, $attachment=NULL) {

    // $this->info('sendEmail');
    // $this->info('attachment: '.$attachment);

    $email_csh = app()->environment('production') ? $branch->email : env('DEV_CSH_MAIL');
    $e = [];
    if (app()->environment('production')) {
      
      // $this->info('bossBranch getUsers');
      $rep = $this->bossBranch->getUsersByBranchid($branch->id);
      $this->info(print_r($rep));
      
      if (is_null($rep)) {
      // $this->info('NULL bossBranch getUsers');
        $e['mailing_list'] = [
          ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
          ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
        ];
      } else {
      // $this->info('NOT NULL bossBranch getUsers');
        $e['mailing_list'] = [];
        foreach ($rep as $k => $u) {
          array_push($e['mailing_list'],
            [ 'name' => $u->name, 
              'email' => $u->email ]
          );
        }
      }
    } else {
      $e['mailing_list'] = [
        ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
        ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
      ];
    }
    // $this->info('sendEmail init mail');

    $e['subject'] = 'APN '.$branch->code.' '.$date->format('Ymd'). ' - New Expense Record from Head Office';
    $e['attachment'] = $attachment;
    $e['csh_email'] = $email_csh;
  
    // $this->info('sendEmail Send');
    \Mail::send('docu.apd.mail-notify', $e, function ($m) use ($e) {
        $m->from('giligans.app@gmail.com', 'GI Head Office');

        if (app()->environment('production')) 
          $m->to($e['csh_email'])->cc('jefferson.salunga@gmail.com')->subject($e['subject']);
        else
          $m->to('jefferson.salunga@gmail.com')->subject($e['subject']);

        // $m->to('jefferson.salunga@gmail.com')->subject($e['subject']);


        if (!is_null($e['attachment']))
          $m->attach($e['attachment']);
    });

  }

  
  
  private function notify($msg) {
  	if(app()->environment()=='production')
      event(new Notifier('Cron\PurchaseNew: '.$msg));
    else
      $this->error($msg);
  }

  
}