<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\BackupRepository;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use App\Models\Process;

class LoadBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load backup for process';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $backup;
    protected $process;

    public function __construct(BackupRepository $backuprepository, Process $process)
    {
      parent::__construct();
      $this->process = $process;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        



    $dir = storage_path().DS.'process';
      
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
      

    foreach($files as $file) {
      
      if ($file->isDir()){
        $this->comment('directory: '.$file);  
      } else {
        $filename = basename($file);
        $code = basename(pathinfo($file, PATHINFO_DIRNAME));
        $this->comment($code.' '.$filename);  


        $res = $this->process->create(['filename'=> $filename, 'code'=>$code, 'path'=> DS.$code.DS.$filename]);
      
        $res ?  $this->comment('saved!'):$this->comment('not saved!');
      }


    }
      
    

        
    }


   
}
