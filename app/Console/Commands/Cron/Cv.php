<?php namespace App\Console\Commands\Cron;

use DB;
use Exception;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use App\Models\FileUpload;
use Illuminate\Console\Command;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Repositories\FileUploadRepository as FileUploadRepo;
use App\Events\Notifier;
use App\Events\Upload\Ap as ApUpload;
use App\Services\DbfImporter;

class Cv extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'cron:cv';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $storage;
  protected $fileUpload;
  protected $dbfImporter;
  protected $filetype_id = '11EAC4B889AAB89BE19440F69C65A020'; // CV
  protected $user_id = '11E775C18F29696AD5F13842AC687868'; // fmarquez

  public function __construct(FileUpload $fileUpload, DbfImporter $dbfImporter)
  {
    parent::__construct();
    $this->fileUpload = $fileUpload;
    $this->dbfImporter = $dbfImporter;
    $this->storage = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
  }

  public function handle() {

    $this->info(app()->environment());
    
    //$fr = $to->copy()->subDays(30);
    $fr = Carbon::parse('2017-01-01');
    $to = Carbon::parse('2017-12-31');
    // $fr = Carbon::parse('2020-01-01');
    // $to = Carbon::now();
    
    if (app()->environment()=='production') {
      $branches = Branch::where('opendate', '<>', '0000-00-00')->where('closedate', '=', '0000-00-00')->orderBy('code')->get();
      $cmd = NULL;
    } else {
      $branches =  Branch::orderBy('code')->get();
      $cmd = $this;
    }

    $this->info('starting...');


    foreach ($branches as $key => $branch) {

      $this->info(' '. $branch->code .' ');

      foreach (dateInterval($fr->format('Y-m-d'), $to->format('Y-m-d')) as $key => $day) {

        $dir = 'CV'.DS.$day->format('Y').DS.$branch->code.DS.$day->format('m').DS.$day->format('d');
      
        if ($this->storage->exists($dir)) {
          
          $this->line($branch->code.' '. $day->format('Y-m-d') .' new directory found! '); // a folder of CV exists on /CV/{YYYY}/{BRCODE}/{MM}/{DD}
         
          $f = $this->fileUpload->where('branch_id', $branch->id)->where('uploaddate',$day->format('Y-m-d'))->where('filetype_id',$this->filetype_id)->first();

          if ($f) {
            $this->info('already processed!');
          } else {

            
            DB::beginTransaction();
            

            $fi = $this->storage->folderInfo2($dir);
            $count = count($fi);

            if ($count>0) {
              $r = [];
              foreach ($fi['files'] as $key => $file) {
                $r[$key] = substr($file['name'], 6);

                // $this->info($file['name']);
                if (ends_with($file['name'], 'CV.DBF')) {
                  $this->info($file['name']);

                  $cvhdrImporter = $this->dbfImporter->invoke('cvhdr');
                  try {
                    $cnt = $cvhdrImporter->import($branch->id, $day, $file['realFullPath'], $cmd);
                  } catch (Exception $e) {
                    DB::rollback();
                    die($e->getMessage());
                  }
                }

                if (ends_with($file['name'], 'DT.DBF')) {
                  $this->info($file['name']);

                  $cvinvdtlImporter = $this->dbfImporter->invoke('cvinvdtl');
                  try {
                    $cnt = $cvinvdtlImporter->import($branch->id, $day, $file['realFullPath'], $cmd);
                  } catch (Exception $e) {
                    DB::rollback();
                    die($e->getMessage());
                  }
                }


              }
              $remarks = join(',', $r);
              $this->comment(' '. $remarks .' ');

              $fu = new FileUpload;
              $fu->branch_id = $branch->id;
              $fu->filename = $day->format('mdy');
              $fu->size = $count;
              $fu->filetype_id = $this->filetype_id;
              $fu->year = $day->format('Y');
              $fu->month = $day->format('m');
              $fu->uploaddate = $day->format('Y-m-d').' 00:00:00';
              $fu->processed = 1;
              $fu->cashier = 'gi.afd01@gmail.com';
              $fu->system_remarks = $remarks;
              $fu->user_id = $this->user_id;
              
              if($fu->save()) {
              //   if (app()->environment()==='production')
              //     event(new ApUpload($fu, $branch));
              }
            }

            DB::commit();

          }
        } else {
          //$this->info(' '. $day->format('Y-m-d') .' ');
        }
      }
    }      
  } 
}