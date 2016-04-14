<?php namespace App\Repositories;

use Carbon\Carbon;
use Dflydev\ApacheMimeTypes\PhpRepository;
use Illuminate\Support\Facades\Storage;
use File;
use App\Repositories\Repository;
use App\Models\Backup;
use App\Models\DailySales;
use App\Repositories\DailySalesRepository;
use App\Repositories\PurchaseRepository as Purchase;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;

class PosUploadRepository extends Repository
{
    
    public $ds;
    public $extracted_path;
    public $purchase;

    

    /**
     * @param App $app
     * @param Collection $collection
     * @throws \App\Repositories\Exceptions\RepositoryException
     */
    public function __construct(App $app, Collection $collection, DailySalesRepository $dailysales, Purchase $purchase) {
        parent::__construct($app, $collection);

        $this->ds = $dailysales;
        $this->purchase = $purchase;
    }

    public function model() {
        return 'App\Models\Backup';
    }




    public function extract($src, $pwd=NULL){
     
      $dir = $this->realFullPath($src);
      $zip = new ZipArchive();
      $zip_status = $zip->open($dir);

      if($zip_status === true) {

        if(!is_null($pwd))
          $zip->setPassword($pwd);
        
        $path = storage_path().DS.'backup'.DS.pathinfo($src, PATHINFO_FILENAME);
        
        if(is_dir($path)) {
          $this->removeDir($path);
        }
        mkdir($path, 0777, true);
         
        $this->extracted_path = $path;

        if(!$zip->extractTo($path)) {
          $zip->close();
          return false;
        }

        //$this->postDailySales($path, filename_to_date2(pathinfo($src, PATHINFO_FILENAME)));
        //$this->removeDir($path);

        $zip->close();

        return true;
      } else {
        return false;
      }
    }


    public function getBackupCode() {
      $dbf_file = $this->extracted_path.DS.'SYSINFO.DBF';

      if (file_exists($dbf_file)) { 
        $db = dbase_open($dbf_file, 0);
        $row = dbase_get_record_with_names($db, 1);
        $code = trim($row['GI_BRCODE']);

        dbase_close($db);
        if(empty($code)) {
          throw new \Exception("Cannot locate Branch Code on backup");
        }
        else 
          return $code;
      } else {
        throw new \Exception("Cannot locate SYSINFO"); 
      }
      
    }

    public function postDailySales(Backup $backup){

      $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        $last_ds = $this->ds->lastRecord();
        $update = 0;
        for ($i = 1; $i <= $record_numbers; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $vfpdate = vfpdate_to_carbon(trim($row['TRANDATE']));
          
          $sales      = ($row['CSH_SALE'] + $row['CHG_SALE'] + $row['SIG_SALE']);
          $empcount   = ($row['CREW_KIT'] + $row['CREW_DIN']);
          $tips       = empty(trim($row['TIP'])) ? 0: trim($row['TIP']);
          $custcount  = empty(trim($row['CUST_CNT'])) ? 0 : trim($row['CUST_CNT']);
          $headspend  = $custcount=='0' ? 0:($sales/$custcount);
          $tipspct    = ($sales=='0.00' || $sales=='0') ? 0 : (($tips/$sales)*100);
          $mancostpct = ($sales=='0.00' || $sales=='0') ? 0 : ((session('user.branchmancost')*$empcount)/$sales)*100;


          // back job on posting purchased 
          if ( $vfpdate->format('Y-m')==$backup->date->format('Y-m') // trans date equal year & mons of backup
          && $backup->date->format('Y-m-d')==$backup->date->endOfMonth()->format('Y-m-d') // if the backupdate = mon end date
          && $backup->date->lte(Carbon::parse('2016-04-01'))) // only backup less than april 1
          {
            $this->postPurchased($vfpdate);
            $this->logAction($vfpdate->format('Y-m-d'), '', base_path().DS.'logs'.DS.'GLV'.DS.$vfpdate->format('Y-m-d').'-PO.txt');
          } 


          if(is_null($last_ds)) {

            $attrs = [
              'date'      => $vfpdate->format('Y-m-d'),
              'branchid'  => session('user.branchid'),
              'managerid' => session('user.id'),
              'sales'     => $sales,
              'tips'      => $tips,
              'custcount' => $custcount,
              'empcount'  => $empcount,
              'headspend' => number_format($headspend,2, '.', ''),
              'tipspct'   => number_format($tipspct,2, '.', ''),
              'mancostpct'=> number_format($mancostpct,2, '.', ''),
              'cospct'    => number_format(0,2, '.', '')
            ];

            if ($this->ds->firstOrNew($attrs, ['date', 'branchid']));
              $update++;
          
          } else {


           


            if($last_ds->date->lte($vfpdate)) { //&& $last_ds->date->lte(Carbon::parse('2016-01-01'))) { 

              if($i==$record_numbers) {
                $attrs = [
                  'date'      => $vfpdate->format('Y-m-d'),
                  'branchid'  => session('user.branchid'),
                  'managerid' => session('user.id'),
                  'sales'     => $sales,
                ];
              } else {
                $attrs = [
                  'date'      => $vfpdate->format('Y-m-d'),
                  'branchid'  => session('user.branchid'),
                  'managerid' => session('user.id'),
                  'sales'     => $sales,
                  'tips'      => $tips,
                  'custcount' => $custcount,
                  'empcount'  => $empcount,
                  'headspend' => number_format($headspend,2, '.', ''),
                  'tipspct'   => number_format($tipspct,2, '.', ''),
                  'mancostpct'=> number_format($mancostpct,2, '.', ''),
                  'cospct'    => number_format(0,2, '.', '')
                ];
              }
              
              if ($this->ds->firstOrNew($attrs, ['date', 'branchid']));
                $update++;
            }
          }
        }
        dbase_close($db);
        return count($update>0) ? true:false;
      }

      return false;
    }




    public function postPurchased(Carbon $date) {
      
      $dbf_file = $this->extracted_path.DS.'PURCHASE.DBF';

      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        $tot_purchase = 0;
        $update = 0;

        // delete if exist
        $this->purchase->deleteWhere(['branchid'=>session('user.branchid'), 'date'=>$date->format('Y-m-d')]);

        for ($i = 1; $i <= $record_numbers; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));

          if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
            //$this->logAction($vfpdate->format('Y-m-d'), trim($row['COMP']), base_path().DS.'logs'.DS.'GLV'.DS.$vfpdate->format('Y-m-d').'-PO.txt');
            $tcost = trim($row['TCOST']);

            $attrs = [
              'comp'      => trim($row['COMP']),
              'unit'      => trim($row['UNIT']),
              'qty'       => trim($row['QTY']),
              'ucost'     => trim($row['UCOST']),
              'tcost'     => $tcost,
              'date'      => $vfpdate->format('Y-m-d'),
              'supno'     => trim($row['SUPNO']),
              'supname'   => trim($row['SUPNAME']),
              'catname'   => trim($row['CATNAME']),
              'vat'       => trim($row['VAT']),
              'terms'     => trim($row['TERMS']),
              'branchid'  => session('user.branchid')
            ];
            //\DB::beginTransaction();
            $this->purchase->create($attrs);
            //\DB::rollBack();
            $tot_purchase += $tcost;
            $update++;
          }



        }


        

        $this->ds->firstOrNew(['branchid'=>session('user.branchid'), 
                              'date'=>$date->format('Y-m-d'),
                              'purchcost'=>$tot_purchase],
                              ['date', 'branchid']);

        

        dbase_close($db);
        return count($update>0) ? true:false;
      }
      return false;
    }


    public function logAction($action, $log, $logfile=NULL) {
      $logfile = !is_null($logfile) 
        ? $logfile
        : base_path().DS.'logs'.DS.now().'-log.txt';

      $dir = pathinfo($logfile, PATHINFO_DIRNAME);

      if(!is_dir($dir))
        mkdir($dir, 0775, true);

      $new = file_exists($logfile) ? false : true;
      if($new){
        $handle = fopen($logfile, 'w+');
        chmod($logfile, 0775);
      } else
        $handle = fopen($logfile, 'a');

      $ip = clientIP();
      $brw = $_SERVER['HTTP_USER_AGENT'];
      //$content = date('r')." | {$ip} | {$action} | {$log} \t {$brw}\n";
      $content = "{$action} | {$log}\n";
      fwrite($handle, $content);
      fclose($handle);
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
      rmdir($dir);
    }


    public function removeExtratedDir() {
      return $this->removeDir($this->extracted_path);
    }

    public function lastRecord() {
        $this->applyFilters();
        return $this->model->orderBy('uploaddate', 'DESC')->first();
    }

    public function ds(){
      return $this->ds->all();
    }
    
  








    /**
   * Return files and directories within a folder
   *
   * @param string $folder
   * @return array of [
   *    'folder' => 'path to current folder',
   *    'folderName' => 'name of just current folder',
   *    'breadCrumbs' => breadcrumb array of [ $path => $foldername ]
   *    'folders' => array of [ $path => $foldername] of each subfolder
   *    'files' => array of file details on each file in folder
   * ]
   */
  public function folderInfo($folder)
  {
    $folder = $this->cleanFolder($folder);

    $folder2 = $this->cleanFolder($this->changeRoot($folder));

    $type = $this->filetype($folder);
    $breadcrumbs = $this->breadcrumbs($folder);
    $slice = array_slice($breadcrumbs, -1);
    $folderName = current($slice);
    $breadcrumbs = array_slice($breadcrumbs, 0, -1);
    $vals = explode('/', $folder);
    $x = empty($vals[1]) ? $folderName:$vals[1];

    $subfolders = [];
    foreach (array_unique($this->disk->directories($folder2)) as $subfolder) {
      $subfolder = $this->changeRoot2($subfolder, $x);
      $subfolders["/$subfolder"] = basename($subfolder);
    }

    $files = [];
    foreach ($this->disk->files($folder2) as $path) {
        $files[] = $this->fileDetails($path);
        //$files[] = config('gi-dtr.upload_path')[app()->environment()].$path;
    }

    return compact(
      'folder',
      'folderName',
      'breadcrumbs',
      'subfolders',
      'files'
    );
  }

  public function changeRoot($folder){
    return str_replace(['files', 'pos'], session('user.branchcode'), $folder);
  }

  public function changeRoot2($folder, $type){
    return str_replace(session('user.branchcode'), $type ,$folder);
  }

  /**
   * Sanitize the folder name
   */
  protected function cleanFolder($folder)
  {

    return '/' . trim(str_replace('..', '', $folder), '/');
    //return trim(str_replace('..', '', $folder), '/');
  }

  /**
   * Return breadcrumbs to current folder
   */
  protected function breadcrumbs($folder)
  {
    $folder = trim($folder, '/');
    $crumbs = ['/' => session('user.branchcode')];

    if (empty($folder)) {
      return $crumbs;
    }

    $folders = explode('/', $folder);
    $build = '';
    foreach ($folders as $folder) {
      $build .= '/'.$folder;
      $crumbs[$build] = $folder;
    }

    return $crumbs;
  }

  /**
   * Return an array of file details for a file
   */
  protected function fileDetails($path)
  {
    //$path = '/' . ltrim($path, '/');
    $path = $path;

    return [
      'name' => basename($path),
      'fullPath' => $path,
      'realFullPath' => $this->realFullPath($path),
      'webPath' => $this->fileWebpath($path),
      'mimeType' => $this->fileMimeType($path),
      'size' => $this->fileSize($path),
      'modified' => $this->fileModified($path),
      'type' => $this->filetype($path)
    ];
  }

  public function realFullPath($path){
    //return config('gi-dtr.upload_path.pos.'.app()->environment()).$path;
    return config('gi-dtr.upload_path.web').$path;
  }



  /**
   * Return the full web path to a file
   */
  public function fileWebpath($path)
  {
    $path = rtrim('uploads', '/') . '/' .
        ltrim($path, '/');
    return url($path);
  }

  /**
   * Return the mime type
   */
  public function fileMimeType($path)
  {
      return $this->mimeDetect->findType(
        strtolower(pathinfo($path, PATHINFO_EXTENSION))
      );
  }

  public function filetype($path){
    if(strtolower(pathinfo($path, PATHINFO_EXTENSION))==='zip')
      return 'zip';
    if(strtolower(pathinfo($path, PATHINFO_EXTENSION))==='png' || 
      strtolower(pathinfo($path, PATHINFO_EXTENSION))==='jpg'  || 
      strtolower(pathinfo($path, PATHINFO_EXTENSION))==='jpeg' || 
      strtolower(pathinfo($path, PATHINFO_EXTENSION))==='gif')
      return 'img';
    return 'file';
  }

  /**
   * Return the file size
   */
  public function fileSize($path)
  {
    return $this->disk->size($path);
  }

  /**
   * Return the last modified time
   */
  public function fileModified($path)
  {
    return Carbon::createFromTimestamp(
      $this->disk->lastModified($path)
    );
  }





  // Add the 4 methods below to the class
  /**
   * Create a new directory
   */
  public function createDirectory($folder)
  {
    $folder = $this->cleanFolder($folder);

    if ($this->disk->exists($folder)) {
      return "Folder '$folder' aleady exists.";
    }

    return $this->disk->makeDirectory($folder);
  }

  /**
   * Delete a directory
   */
  public function deleteDirectory($folder)
  {
    $folder = $this->cleanFolder($folder);

    $filesFolders = array_merge(
      $this->disk->directories($folder),
      $this->disk->files($folder)
    );
    if (! empty($filesFolders)) {
      return "Directory must be empty to delete it.";
    }

    return $this->disk->deleteDirectory($folder);
  }

  /**
   * Delete a file
   */
  public function deleteFile($path)
  {
    $path = $this->cleanFolder($path);

    if (! $this->disk->exists($path)) {
      return "File does not exist.";
    }

    return $this->disk->delete($path);
  }

  /**
   * Save a file
   */
  public function saveFile($path, $content, $exist=true)
  {
    $path = $this->cleanFolder($path);

    if($exist) {
      if ($this->disk->exists($path)) {
        return "File already exists.";
      }
    }

    return $this->disk->put($path, $content);
  }

  public function exists($path){
    return $this->disk->exists($path);
  }

  /**
   * Move a file
   */
  public function moveFile($src, $target, $exist=true)
  {
    $path = $this->cleanFolder($target);
    $dir = pathinfo($this->realFullPath($path));

    if($exist) {
      if ($this->disk->exists($path)) {
        //return "File already exists...";
        throw new \Exception("File ".$dir['basename'].'.'.$dir['extension']." already exists on storage ".$this->type);        
      }
    }

   

    
    if(!is_dir($dir['dirname']))
      mkdir($dir['dirname'], 0775, true); //$this->createDirectory($dir);

    //return $this->disk->move($src, $target);

    try {
      File::move($src, $this->realFullPath($path));
    }catch(\Exception $e){
      throw new \Exception("Error: ". $e->getMessage());    
    }
  }

  public function get($path){
    return file_get_contents($this->realFullPath($path));
  }

  


    
}