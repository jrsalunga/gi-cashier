<?php namespace App\Console\Commands\Import;

use DB;
use App\Models\Branch;
use App\Helpers\Locator;
use Illuminate\Console\Command;
use App\Repositories\PosUploadRepository as PosUploadRepo;

class Product extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'import:product {brcode : Branch Code} {backup : Backup File}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Import the PRODUCTS.DBF to Product table';

  protected $posUploadRepo;

  public function __construct(PosUploadRepo $posUploadRepo)
  {
    parent::__construct();
    $this->posUploadRepo = $posUploadRepo;
  }
  



  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
   
    $br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
    if (!$br) {
      $this->info('Invalid Branch Code.');
      exit;
    }
    

    $backup = strtoupper($this->argument('backup'));
    if (!is_pos_backup($backup)) {
      $this->info('Invalid Backup.');
      exit;
    }
    $d = backup_to_carbon_date($backup);

    
    $locator = new Locator('pos');
    $path = $br->code.DS.$d->format('Y').DS.$d->format('m').DS.'GC'.$d->format('mdy').'.ZIP';
    if (!$locator->exists($path)) {
      $this->info('Backup '.$path.' do not exist.');
      exit;
    }

    if (app()->environment('production')) {
      $bckup = $this->posUploadRepo->findWhere(['branchid'=>$br->id, 'filename'=>$backup])->first();
      if (!$bckup) {
        $this->info('No record found on database.'); 
        exit;
      }
    }

    $this->info(' Extracting...');
    if (!$this->extract($locator->realFullPath($path), $br->code)) {
      $this->info('Unable to extract '. $backup .', the backup maybe corrupted. Try to generate another backup file and try to re-upload.');
      exit;
    }

    $this->info(' Start processing...');

    DB::beginTransaction();

    
    $this->info(' importing products table...');
    try {
      $res = $this->importProducts();
    } catch (Exception $e) {
      $this->info($e->getMessage());
      $this->removeExtratedDir();
      DB::rollback();
      exit;
    }

    $this->info(' row:'. $res);


    DB::commit();
    //DB::rollback();

    $this->info('done');
    $this->removeExtratedDir();
    exit;
  }



   private function extract($filepath, $brcode) {
    return $this->posUploadRepo->extract($filepath, 'admate', false, $brcode);  
  }

  private function removeExtratedDir() {
    return $this->posUploadRepo->removeExtratedDir();
  }


  private function importProducts() {
    try {
      return $this->posUploadRepo->updateProductsTable();  
    } catch(Exception $e) {
      throw $e;    
    }
  }
}
