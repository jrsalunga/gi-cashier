<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\BackupRepository;

class ProcessBackupFiledate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:filedate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process backup filedate';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $backup;

    public function __construct(BackupRepository $backuprepository)
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
      /*
      $backups = \App\Models\Backup::all();

      foreach ($backups as $backup) {

        $date = $this->backupParseDate($backup->filename);
        
        if($date) {


          $this->comment('parsing: '. $backup->filename);

          $backup->filedate = $date->format('Y-m-d').' '.$backup->uploaddate->format('H:m:i');
          
          if($backup->save()) 
            $this->comment('saved!');
          else 
            $this->comment('not saved!');



        }


      }

        */

        
    }


    private function backupParseDate($filename) {

      $f = pathinfo($filename, PATHINFO_FILENAME);

      $m = substr($f, 2, 2);
      $d = substr($f, 4, 2);
      $y = '20'.substr($f, 6, 2);
      
      if(is_iso_date($y.'-'.$m.'-'.$d))
        return carbonCheckorNow($y.'-'.$m.'-'.$d);
      else 
        return false;
    }
}
