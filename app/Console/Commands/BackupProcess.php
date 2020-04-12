<?php

namespace App\Console\Commands;

use DB;
use Exception;
use Illuminate\Console\Command;
use App\Repositories\BackupRepository;
use App\Repositories\PosUploadRepository;
use App\Models\Process;
use App\Models\Branch;
use Carbon\Carbon;
use App\Repositories\Filters\ByBranch;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;

class BackupProcess extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'process:backup';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Extract and process backup';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  protected $backup;
  protected $posrepo;
  protected $process;
  protected $branch;

  public function __construct(
    BackupRepository $backup,
    PosUploadRepository $posrepo,
    Process $process,
    Branch $branch,
    PhpRepository $mimeDetect)
  {
      parent::__construct();
      $this->posrepo = $posrepo;
      $this->posrepo->pushFilters(new ByBranch(request()));
      $this->backup = $backup;
      $this->process = $process;
      $this->process = $process;
      $this->branch = $branch;
      $this->pos = new StorageRepository($mimeDetect, 'pos.'.app()->environment());
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle()
  {
    $msg = '';
    $process = $this->process
                    ->where('processed', '0')
                    ->orderBy('code', 'DESC')
                    ->orderBy('filedate', 'DESC')
                    ->first();

    if(!is_null($process)) {
      $this->logAction('start:submit', $msg);
      $path = storage_path().DS.'process'.$process->path;

      $this->comment('locating file '.$process->filename);
      if(file_exists($path)) {

        $d = $this->backupParseDate($process->filename);

        $branch = $this->branch->where('code', $process->code)->first();

        $storage_path = $process->code.DS.$d->format('Y').DS.$d->format('m').DS.$process->filename;

        $this->comment('creating backup record for '.$branch->code.' - '.$branch->descriptor);
        $backup = $this->createPosUpload($path, $process->filename, $branch->id);

        $this->comment('extracting '.$process->filename.'...');
        if(!$this->extract($path)){
          $msg =  'Unable to extract '. $backup->filename;
          $msg .= $d ? ' & deleted':'';
          $this->comment($msg);
          $this->removeExtratedDir();
          //DB::rollBack();
          $this->updateBackupRemarks($backup, $msg);
          $this->logAction('error:extract:backup', $msg);
          exit();
          //return redirect('/backups/upload')->with('alert-error', $msg);
        }
        $this->logAction('success:extract:backup', $process->filename);
        $this->comment('success extracting!');

        DB::beginTransaction();

        
        $this->comment('process daily sales...');
        try {
            $this->processDailySales($backup);
          } catch (Exception $e) {
            $msg =  $e->getMessage();
            $msg .= $d ? ' & deleted':'';
            $this->comment($msg);
            DB::rollBack();
            $this->removeExtratedDir();
            $this->updateBackupRemarks($backup, $msg);
            $this->updateProcessRemarks($process, $msg);
            $this->logAction('error:process:backup', $msg);
            exit();
          }
        $this->logAction('success:process:backup', $process->filename);
        $this->comment('success process daily sales!');

        
        $this->comment('process '.$d->format('Y-m-d').' purchased...');
        try {
            $this->processPurchased($backup->date, $backup);
          } catch (Exception $e) {
            $msg =  $e->getMessage();
            $msg .= $d ? ' & deleted':'';
            $this->comment($msg);
            DB::rollBack();
            $this->removeExtratedDir();
            $this->updateBackupRemarks($backup, $msg);
            $this->updateProcessRemarks($process, $msg);
            $this->logAction('error:process:purchased', $msg);
            exit();
          }
        $this->logAction('success:process:purchased', $process->filename);
        $this->comment('success process purchased!');
        

        $this->comment('removing extrated '.$process->filename);
        $this->removeExtratedDir();
        
        $this->comment('moving '.$process->filename);
        $this->comment('from: '.$path);
        $this->comment('to storage path: '.$storage_path);
        try {
          $this->pos->moveFile($path, $storage_path, false); // false = override file!
        } catch(Exception $e){
          $msg =  $e->getMessage();
          $this->comment($msg);
          DB::rollBack();
          $this->removeExtratedDir();
          $this->updateBackupRemarks($backup, $msg);
          $this->updateProcessRemarks($process, $msg);
          $this->logAction('error:move:backup', $msg);
          exit();    
        }
        $this->comment('success moving file');
        $this->logAction('success:move:backup', $process->filename);
        
        //DB::rollBack();
        DB::commit();

        $this->logAction('end:submit', $msg);
        $this->comment($process->filename.' has been processed!');
        $this->updateProcessRemarks($process, 'success');
        exit();
      }
      $msg = 'error on locating file: '.$path;
      $this->logAction('error:locating:file', $path);
      $this->comment($msg);
      $this->updateProcessRemarks($process, $msg);
    }
    $this->comment('all has been processed!');
  }


  private function updateProcessRemarks($process, $msg) {
    $process->processed = 1;
    $process->note = $msg;
    $process->save();
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

  public function createPosUpload($src, $filename, $branchid){

    $d = $this->backupParseDate($filename);

    $data = [
      'branchid' => $branchid,
      'filename' => $filename,
      'year' => $d->format('Y'), //$request->input('year'),
      'month' => $d->format('m'), //$request->input('month'),
      'size' => filesize($src),
      'mimetype' => 'application/zip',
      'terminal' => '127.0.0.1', //$request->ip(),
      'userid' => '29E4E2FA672C11E596ECDA40B3C0AA12',
      'filedate' => $d->format('Y-m-d').' '.Carbon::now()->format('H:i:s'),
      'remarks' => 'scheduled task run @ server',
      'cashier' => 'bot'
    ];

    return $this->backup->create($data)?:NULL;
  }




  public function extract($filepath) {
    return $this->posrepo->extract2($filepath, 'admate'); 
  }

  public function processDailySales($posupload){
    //$this->backup->extract($filepath, 'admate');
    try {
      $this->posrepo->postDailySales2($posupload);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
    $this->backup->update(['processed'=>1], $posupload->id);
  }


  public function processPurchased($date, $backup){
    try {
      $this->posrepo->postPurchased2($date, $backup);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
  }

  private function logAction($action, $log) {
    $logfile = base_path().DS.'logs'.DS.now().'-process-backup-log.txt';

    $dir = pathinfo($logfile, PATHINFO_DIRNAME);

    if(!is_dir($dir))
      mkdir($dir, 0775, true);

    $new = file_exists($logfile) ? false : true;
    if($new){
      $handle = fopen($logfile, 'w+');
      chmod($logfile, 0775);
    } else
      $handle = fopen($logfile, 'a');


    $content = date('r')." | {$action} | {$log} \n";
    fwrite($handle, $content);
    fclose($handle);
  } 

  public function updateBackupRemarks($posupload, $message) {
    $x = explode(':', $posupload->remarks);
    $msg = empty($x['1']) 
      ? $posupload->remarks.' '. $message
      : $posupload->remarks.', '. $message;
          
    return $this->backup->update(['remarks'=> $msg], $posupload->id);
  }

  public function removeExtratedDir() {
    return $this->posrepo->removeExtratedDir();
  }

  public function ds(Request $request) {
    $this->backup->ds->pushFilters(new WithBranch(['code', 'descriptor', 'id']));
    return $this->backup->ds->lastRecord();
  }
}
