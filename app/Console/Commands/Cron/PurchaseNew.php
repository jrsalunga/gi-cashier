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

	protected $signature = 'cron:purchase-new {--check=false : Run checker only}';
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

    
    $this->info(print_r($files));
    
    // check if there is a backup file on staging folder to process.
    if (count($files)>0 || $this->option('check')=='true') {
      
      foreach ($files as $idx => $file) {

        $boom = explode(DS, $file);
        $cnt = count($boom);  // count the explode segment if 8 array
        // $this->info(print_r($boom));
        // $this->info($cnt); 

        if (ends_with($file, '.NEW') && $cnt==8) {

          // $this->info($file); 

          $this->filepath = $file;

          
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
            // $this->info('apd_dir: '.$apd_dir); 

           
            // copy to processed
            $dir = $factory_path.DS.'PROCESSED'.DS.$apd_dir;
            $destp = $dir.DS.$filename;

            if (!is_dir($dir))
              mkdir($dir, 0777, true);
           
            try {
              File::copy($this->filepath, $destp);
            } catch(Exception $e){
              throw new Exception("Error copy to PROCESSED. ". $e->getMessage());    
            }


            // move to APD 
            $dest = $this->fileStorage->realFullPath($apd_dir);
            $apd_filepath = $dest.DS.$filename;

            if (!is_dir($dest))
              mkdir($dest, 0775, true);

            try {
              if (app()->environment()=='local')
                File::copy($this->filepath, $apd_filepath);
              else   
                // File::copy($this->filepath, $apd_filepath);
                File::move($this->filepath, $apd_filepath);
            } catch(Exception $e){
              throw new Exception("Error move to APD. ". $e->getMessage());    
            }


            // $this->info('apd_filepath: '.$apd_filepath); 

            $rcpt_array = $this->aggregateReceipt($this->dbfToArray($apd_filepath));


            $this->sendEmail($br, $date, $rcpt_array, $apd_filepath);

            // test_log($date->format('Y-m-d').','.$br->code.','.Carbon::now()->format('Y-m-d').','.Carbon::now()->format('H:i:s'), $factory_path.DS.'STAGING'.DS.$date->format('Y').'-purchase.new.log');

          } // end: ==='PURCHASE.NEW'
        } // end: ends_with($file)
        if (!is_null($cmd)) 
          $this->info($idx.'. No PURCHASE.NEW found.');
      
      } // end: foreach(files)
    } // end: count($files)
    if ($this->option('check')=='true')
      $this->info('Run check only');
  }

  private function sendEmail(Branch $branch, Carbon $date, array $data, $attachment=NULL) {

    $e = [];
    $e['csh_email'] = app()->environment('production') ? $branch->email : env('DEV_CSH_MAIL');
    if (app()->environment('production')) {
      
      $rep = $this->bossBranch->getUsersByBranchid($branch->id);
      $this->info($e['csh_email']);
      $this->info(print_r($rep));
      
      if (is_null($rep)) {
        $e['mailing_list'] = [
          ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
          ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
        ];
      } else {
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

    $subj = []; 
    foreach($data as $d)
      if(!in_array($d['supplier'], $subj))
        array_push($subj, $d['supplier']);
  

    $e['subject'] = 'APN '.$branch->code.' - '.$date->format('F').' Payables from '.implode(', ',$subj); //  .' '.c()->format('His');
    // $e['subject'] = 'APN '.$branch->code.' '.$date->format('Ymd'). ' - New '.$date->format('F').' Payables Record to Download from Head Office 💼 📥 '.c()->format('YmdHis');
    $e['attachment'] = $attachment;

    $e['data'] = $data;

    
  
    // \Mail::send('docu.apd.mail-notify', $e, function ($m) use ($e) {
    \Mail::queue('docu.apd.mail-notify', $e, function ($m) use ($e) {
        
      $m->subject($e['subject']);
      $m->from('giligans.app@gmail.com', 'GI Head Office');


      if (app()->environment('production')) {
        $m->to($e['csh_email']);

        foreach ($e['mailing_list'] as $u)
          $m->cc($u['email'], $u['name']);

        $m->cc('jefferson.salunga@gmail.com');
        $m->replyTo('jefferson.salunga@gmail.com');
      } else
        $m->to('jefferson.salunga@gmail.com')->subject($e['subject']);

      if (!is_null($e['attachment']))
        $m->attach($e['attachment']);
    });

  }


  public function dbfToArray($dbf_file) {

    $dbf_data = [];

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      for ($i=1; $i<=$record_numbers; $i++) {
        $r = dbase_get_record_with_names($db, $i);

        $dbf_data[$i] = [
          'COMP' => $r['COMP'],
          'QTY' => $r['QTY'],
          'UNIT' => $r['UNIT'],
          'UCOST' => $r['UCOST'],
          'TCOST' => $r['TCOST'],
          'PODATE' => c($r['PODATE'])->format('Y-m-d'),
          'SUPNO' => $r['SUPNO'],
          'SUPCODE' => substr(trim($r['SUPNO']),2),
          'SUPNAME' => $r['SUPNAME'],
          'TERMS' => $r['TERMS'],
          'FILLER1' => $r['FILLER1'],
          'SUPTIN' => $r['SUPTIN'],
          'BRCODE' => $r['GI_BRCODE'],
          'PCODE' => substr(trim($r['SUPNO']),0,2),
          'ID' => preg_replace("/[^A-Za-z0-9]/", '', substr(trim($r['SUPNO']),2).trim($r['FILLER1'])),
        ];
      }
    }
    // $this->info(print_r($dbf_data));
    return $dbf_data;
  }

  public function aggregateReceipt(array $arr_data) {

    if (empty($arr_data)) 
      return [];

    $rcpt_array = [];

    foreach($arr_data as $key => $comp) {
      // dd($comp['ID']);
      // $this->info($comp['ID']);

      if (array_key_exists($comp['ID'], $rcpt_array)) {

        $ctr++;
        $total += $comp['TCOST'];


        array_push($rcpt_array[$comp['ID']]['items'], 
          [
            'comp' => $comp['COMP'],
            'unit' => $comp['UNIT'],
            'qty' => $comp['QTY'],
            'ucost' => $comp['UCOST'],
            'tcost' => $comp['TCOST'],
            'pcode' => $comp['PCODE'],
          ]
        );

        $rcpt_array[$comp['ID']]['line'] = $ctr;
        $rcpt_array[$comp['ID']]['total'] = $total;

      } else {
        $ctr = 1;
        $total = $comp['TCOST'];

        $rcpt_array[$comp['ID']] = [
          'brcode' => $comp['BRCODE'],
          'supplier' => $comp['SUPNAME'],
          'suppcode' => $comp['SUPCODE'],
          'supptin' => $comp['SUPTIN'],
          'date' => $comp['PODATE'],
          'terms' => $comp['TERMS'],
          'inv' => $comp['FILLER1'],
          'items' => [
            [
              'comp' => $comp['COMP'],
              'unit' => $comp['UNIT'],
              'qty' => $comp['QTY'],
              'ucost' => $comp['UCOST'],
              'tcost' => $comp['TCOST'],
              'pcode' => $comp['PCODE'],
            ]
          ],
          'line' => $ctr,
          'total' => $comp['TCOST'],
        ];
      }
    }

    // $this->info(print_r($rcpt_array));
    return $rcpt_array;
  }


  private function notify($msg) {
  	if(app()->environment()=='production')
      event(new Notifier('Cron\PurchaseNew: '.$msg));
    else
      $this->error($msg);
  }

  
}