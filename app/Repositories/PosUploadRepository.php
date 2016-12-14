<?php namespace App\Repositories;

use File;
use StdClass;
use Exception;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Backup;
use App\Models\DailySales;
use App\Repositories\Repository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use Illuminate\Support\Facades\Storage;
use App\Repositories\DailySalesRepository;
use App\Repositories\PurchaseRepository as Purchase;
use App\Repositories\Purchase2Repository as PurchaseRepo;
use App\Http\Controllers\SalesmtdController as SalesmtdCtrl;
use App\Repositories\ChargesRepository as ChargesRepo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;

class PosUploadRepository extends Repository
{
    
    public $ds;
    public $extracted_path;
    public $purchase;
    public $purchase2;
    public $charges;
    private $sysinfo = null;
    protected $salesmtdCtrl;

    

    /**
     * @param App $app
     * @param Collection $collection
     * @throws \App\Repositories\Exceptions\RepositoryException
     */
    public function __construct(App $app, Collection $collection, DailySalesRepository $dailysales, 
      Purchase $purchase, PurchaseRepo $purchaserepo, SalesmtdCtrl $salesmtdCtrl, ChargesRepo $charges) {
        parent::__construct($app, $collection);

        $this->ds = $dailysales;
        $this->purchase   = $purchase;
        $this->purchase2  = $purchaserepo;
        $this->salesmtdCtrl = $salesmtdCtrl;
        $this->charges = $charges;
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


    private function getSysinfo($r) {
      $s = new StdClass;
      foreach ($r as $key => $value) {
        $f = strtolower($key);
        $s->{$f} = isset($r[$key]) ? $r[$key]:NULL;
      }
      return $s;
    }


    public function getBackupCode() {
      $dbf_file = $this->extracted_path.DS.'SYSINFO.DBF';

      if (file_exists($dbf_file)) { 
        $db = dbase_open($dbf_file, 0);
        $row = dbase_get_record_with_names($db, 1);

        $this->sysinfo = $this->getSysinfo($row);

        $code = trim($row['GI_BRCODE']);

        dbase_close($db);
        if(empty($code)) {
          throw new Exception("Cannot locate Branch Code on backup");
        }
        else 
          return $code;
      } else {
        throw new Exception("Cannot locate SYSINFO"); 
      }
      
    }


    private function getDailySalesDbfRowData($r){
      $row = [];

      $kit = isset($r['CREW_KIT']) ? $r['CREW_KIT']:0;
      $din = isset($r['CREW_DIN']) ? $r['CREW_DIN']:0;
      $tip = isset($r['TIP']) ? $r['TIP']:0;
      $cuscnt = isset($r['CUST_CNT']) ? $r['CUST_CNT']:0;
      $mcost = (isset($r['MAN_COST']) && !empty($r['MAN_COST'])) 
        ? $r['MAN_COST']
        : 0;
      $mcost = ($mcost+0)==0 ? session('user.branchmancost'):$mcost;

      $vfpdate    = vfpdate_to_carbon(trim($r['TRANDATE']));
      $sales      = ($r['CSH_SALE'] + $r['CHG_SALE'] + $r['SIG_SALE']) + 0;
      $empcount   = ($kit + $din);
      //$tips       = empty(trim($r['TIP'])) ? 0: trim($r['TIP']);
      $tips       = $tip;
      //$custcount  = empty(trim($r['CUST_CNT'])) ? 0 : trim($r['CUST_CNT']);
      $custcount  = $cuscnt;
      $headspend  = $custcount==0 ? 0:($sales/$custcount);
      $tipspct    = ($sales=='0.00' || $sales=='0') ? 0 : ($tips/$sales)*100;
      //$brmancost  = ($r['MAN_COST'] * $empcount);
      $mancost    = $mcost*$empcount;

      $mancostpct = ($sales=='0.00' || $sales=='0') ? 0 : ($mancost/$sales)*100;
      $salesemp   = ($empcount=='0.00' || $empcount=='0') ? 0 : $sales/$empcount;

      $row['branchid']  = session('user.branchid');
      $row['managerid'] = session('user.id'); // cashierid actually
      $row['date']      = $vfpdate->format('Y-m-d');
      $row['sales']     = number_format($sales, 2, '.', ''); 
      $row['empcount']  = $empcount;
      $row['tips']      = $tips;
      $row['custcount'] = $custcount;
      $row['headspend'] = number_format($headspend, 2, '.', '');
      $row['tipspct']   = number_format($tipspct, 2, '.', '');
      $row['mancost']   = number_format($mancost, 2, '.', '');
      $row['mancostpct']= number_format($mancostpct, 2, '.', '');
      $row['salesemp']  = number_format($salesemp, 2, '.', '');
      $row['cospct']    = number_format(0, 2, '.', '');
      return $row;
    }


    public function parseCustomerCount(Carbon $date) {
      $dbf_file = $this->extracted_path.DS.'CHARGES.DBF';

      $cust_count = 0;
      
      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);

        for ($i = 1; $i <= $record_numbers; $i++) {
          $row = dbase_get_record_with_names($db, $i);

          $vfpdate = Carbon::parse($row['ORDDATE']);

          if ($date->format('Y-m-d')==$vfpdate->format('Y-m-d')) {

            if (($row['SR_TCUST']==$row['SR_BODY']) && ($row['SR_DISC']>0)) // 4 4 78.7
              $cust_count += $row['SR_TCUST']; 
            else if ($row['SR_TCUST']>0 && $row['SR_BODY']>0 && $row['SR_DISC']>0)
              continue;
            else
              $cust_count += ($row['SR_TCUST'] + $row['SR_BODY']);

          }
        }
        dbase_close($db);
      }
      return $cust_count;
    }

    public function postDailySales(Backup $backup){

      //$this->logAction('function:postDailySales', '');
      $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        $last_ds = $this->ds->lastRecord();
        $update = 0;
        //$this->logAction('start:loop:ds', '');
        for ($i = 1; $i <= $record_numbers; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $data = $this->getDailySalesDbfRowData($row);
          $vfpdate = Carbon::parse($data['date']);

          

          //$this->logAction('loop:ds:'.$vfpdate->format('Y-m-d'), '');
          // back job on posting purchased 
          if ( $vfpdate->format('Y-m')==$backup->date->format('Y-m') // trans date equal year & mons of backup
          && $backup->date->format('Y-m-d')==$backup->date->endOfMonth()->format('Y-m-d') // if the backupdate = mon end date
          && $backup->date->lte(Carbon::parse('2016-06-01'))) // only backup less than april 1
          {
            
            try {
              $this->postPurchased($vfpdate);
            } catch(Exception $e) {
              return false;
              //throw new Exception($e->getMessage());    
            }
            
            //$this->logAction($vfpdate->format('Y-m-d'), '', base_path().DS.'logs'.DS.'GLV'.DS.$vfpdate->format('Y-m-d').'-PO.txt');
     
          } 
          //test_log('date: '. $vfpdate->format('Y-m-d'));
          /*
          // if backup is start of month, update end of last month
          if($vfpdate->format('Y-m-d')==$backup->date->copy()->subDay()->format('Y-m-d')
          && $backup->date->format('Y-m-d')==$backup->date->startOfMonth()->format('Y-m-d'))
          {
            test_log('date: '. $vfpdate->format('Y-m-d'));
            try {
              $this->postPurchased($vfpdate);
            } catch(Exception $e) {
              return false;
              //throw new Exception($e->getMessage());    
            }
          }
          */



          //$this->logAction('ds:get_last', '');
          if(is_null($last_ds)) {

            if ($this->ds->firstOrNew($data, ['date', 'branchid']));
              $update++;
          
          } else {
            /*
            * commented: issue not update DS if the last DS is higher than backup
            */
            //if($last_ds->date->lte($vfpdate)) { //&& $last_ds->date->lte(Carbon::parse('2016-01-01'))) { 
            if( $vfpdate->format('Y-m') == $backup->date->format('Y-m')) 
            {
              // fix cust_count on boss module = 0     - 2016-11-06
              if ($data['custcount']=='0') {
                $data['custcount'] = $this->parseCustomerCount($vfpdate);
                $data['headspend'] = $data['custcount']==0 ? 0:($data['sales']/$data['custcount']);
              }
            
              if($i==$record_numbers) {

                  

                if( isset($this->sysinfo->posupdate) 
                && vfpdate_to_carbon($this->sysinfo->posupdate)->lt(Carbon::parse('2016-07-06')))  // before sysinfo.update
                {
                  if ($this->ds->firstOrNew(array_only($data, ['date', 'branchid', 'managerid', 'sales']), ['date', 'branchid'])) {
                    $update++;
                  }
                } else {
                 
                  
                  if ($this->ds->firstOrNew(array_only($data, 
                    ['date', 'branchid', 'managerid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend']
                  ), ['date', 'branchid'])) {
                    $update++;
                  }
                }
              } else {
                //$this->logAction('single:lte:i!=record_numbers', '');
                if ($this->ds->firstOrNew($data, ['date', 'branchid']))
                  $update++;
              }
            }

            // if FOM update EOM
            if( $backup->date->format('Y-m-d') == $backup->date->copy()->startOfMonth()->format('Y-m-d')
            && $vfpdate->format('Y-m-d') == $backup->date->copy()->subDay()->format('Y-m-d') )
            {
              // fix cust_count on boss module = 0     - 2016-11-06
              //if ($data['custcount']=='0')
               // $data['custcount'] = $this->parseCustomerCount($vfpdate);

              //test_log('last: '. $vfpdate->format('Y-m-d'));
              if ($this->ds->firstOrNew(array_only($data, 
                  ['date', 'branchid', 'managerid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend']
                ), ['date', 'branchid'])) {
                  $update++;
                }
            }


          }
        }
        //$this->logAction('end:loop:ds', '');
        dbase_close($db);
        return count($update>0) ? true:false;
      }

      return false;
    }




    public function postPurchased(Carbon $date) {
      
      $dbf_file = $this->extracted_path.DS.'PURCHASE.DBF';

      //$this->logAction($date->format('Y-m-d'),'post:purchased:file_exists');
      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        $tot_purchase = 0;
        $update = 0;

        // delete if exist
        try {
          //$this->logAction($date->format('Y-m-d'), 'delete:purchased');
          $this->purchase->deleteWhere(['branchid'=>session('user.branchid'), 'date'=>$date->format('Y-m-d')]);
        } catch(Exception $e) {
          throw new Exception($e->getMessage());    
        }


        try {
          //$this->logAction($date->format('Y-m-d'), 'delete:purchased2');
          $this->purchase2->deleteWhere(['branchid'=>session('user.branchid'), 'date'=>$date->format('Y-m-d')]);
        } catch(Exception $e) {
          throw new Exception($e->getMessage());    
        }


        //$this->logAction($date->format('Y-m-d'), 'start:loop:purchased');
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
            //$this->logAction($date->format('Y-m-d'), 'create:purchased');
            try {
              $this->purchase->create($attrs);
            } catch(Exception $e) {
              throw new Exception($e->getMessage());    
            }

            try {
              //$this->logAction($date->format('Y-m-d'), 'create:purchased2');
              $this->purchase2->verifyAndCreate($attrs);
            } catch(Exception $e) {
              throw new Exception($e->getMessage());    
            }
            
            //\DB::rollBack();
            $tot_purchase += $tcost;
            $update++;
          }
        }
        //$this->logAction($date->format('Y-m-d'), 'end:loop:purchased');

        try {
          //$this->logAction($date->format('Y-m-d'), 'update:ds');
          $this->ds->firstOrNew(['branchid'=>session('user.branchid'), 
                              'date'=>$date->format('Y-m-d'),
                              'purchcost'=>$tot_purchase],
                              ['date', 'branchid']);
        } catch(Exception $e) {
          throw new Exception($e->getMessage());    
        }

        

        dbase_close($db);
        return count($update>0) ? true:false;
      }
      return false;
    }


    public function logAction($action, $log, $logfile=NULL) {
      $logfile = !is_null($logfile) 
        ? $logfile
        : base_path().DS.'logs'.DS.session('user.branchcode').DS.now().'-log.txt';

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
      $content = date('r')." | {$ip} | {$action} | {$log} \t {$brw}\n";
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
    //$path = $this->cleanFolder($target);
    $path = $target;
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
      throw new \Exception("Error ". $e->getMessage());    
    }
  }

  public function get($path){
    return file_get_contents($this->realFullPath($path));
  }






  public function extract2($src, $pwd=NULL){
     
    //$dir = $this->realFullPath($src);
    $dir = $src;
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



  public function postDailySales2(Backup $backup){

      //$this->logAction('function:postDailySales', '');
      $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        //$last_ds = $this->ds->lastRecord();
        $last_ds = NULL;
        $update = 0;

        for ($i = 1; $i <= $record_numbers; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $data = $this->getDailySalesDbfRowData($row);
          $vfpdate = Carbon::parse($data['date']);

          // back job on posting purchased 
          if ( $vfpdate->format('Y-m')==$backup->date->format('Y-m') // trans date equal year & mons of backup
          //&& $backup->date->format('Y-m-d')==$backup->date->endOfMonth()->format('Y-m-d') // if the backupdate = mon end date
          && $backup->date->lte(Carbon::parse('2016-06-01'))) // only backup less than april 1
          {
            
            try {
              $this->postPurchased2($vfpdate, $backup);
            } catch(Exception $e) {
              dbase_close($db);
              throw new Exception($e->getMessage());    
            }
          } 
          
          if(is_null($last_ds)) {

            if ($this->ds->firstOrNew($data, ['date', 'branchid']));
              $update++;
          
          } else {
            /*
            * commented: issue not update DS if the last DS is higher than backup
            */
            //if($last_ds->date->lte($vfpdate)) { //&& $last_ds->date->lte(Carbon::parse('2016-01-01'))) { 
            if($vfpdate->format('Y-m')==$backup->date->format('Y-m')) {

              //$this->logAction('single:lte', '');
            
              if($i==$record_numbers) {
               
                //$this->logAction('single:lte:i==record_numbers', '');
                if ($this->ds->firstOrNew(array_only($data, ['date', 'branchid', 'managerid', 'sales']), ['date', 'branchid']))
                  $update++;

              } else {
                
                //$this->logAction('single:lte:i!=record_numbers', '');
                if ($this->ds->firstOrNew($data, ['date', 'branchid']))
                  $update++;

              }
            }


          }
        }
        //$this->logAction('end:loop:ds', '');
        dbase_close($db);
        return count($update>0) ? true:false;
      }

      return false;
    }

    public function postPurchased2(Carbon $date, Backup $backup) {
      
      $dbf_file = $this->extracted_path.DS.'PURCHASE.DBF';

      //$this->logAction($date->format('Y-m-d'),'post:purchased:file_exists');
      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        $tot_purchase = 0;
        $update = 0;

        // delete if exist
        try {
          //$this->logAction($date->format('Y-m-d'), 'delete:purchased');
          $this->purchase->deleteWhere(['branchid'=>$backup->branchid, 'date'=>$date->format('Y-m-d')]);
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception($e->getMessage());    
        }


        try {
          //$this->logAction($date->format('Y-m-d'), 'delete:purchased2');
          $this->purchase2->deleteWhere(['branchid'=>$backup->branchid, 'date'=>$date->format('Y-m-d')]);
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception($e->getMessage());    
        }


        //$this->logAction($date->format('Y-m-d'), 'start:loop:purchased');
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
              'branchid'  => $backup->branchid
            ];
            
            //\DB::beginTransaction();
            //$this->logAction($date->format('Y-m-d'), 'create:purchased');
            try {
              $this->purchase->create($attrs);
            } catch(Exception $e) {
              dbase_close($db);
              throw new Exception($e->getMessage());    
            }

            //$this->logAction($date->format('Y-m-d'), 'create:purchased2');
            try {
              $this->purchase2->verifyAndCreate($attrs);
            } catch(Exception $e) {
              dbase_close($db);
              throw new Exception($e->getMessage());    
            }
            
            //\DB::rollBack();
            $tot_purchase += $tcost;
            $update++;
          }
        }
        //$this->logAction($date->format('Y-m-d'), 'end:loop:purchased');

        try {
          //$this->logAction($date->format('Y-m-d'), 'update:ds');
          $this->ds->firstOrNew(['branchid'=>$backup->branchid, 
                              'date'=>$date->format('Y-m-d'),
                              'purchcost'=>$tot_purchase],
                              ['date', 'branchid']);
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception($e->getMessage());    
        }

        

        dbase_close($db);
        return count($update>0) ? true:false;
      }
      return false;
    }



    public function postSalesmtd(Carbon $date, Backup $backup) {

      $dbf_file = $this->extracted_path.DS.'SALESMTD.DBF';

      if (file_exists($dbf_file)) {
        //$this->logAction('posting', 'post:salesmtd');
        $db = dbase_open($dbf_file, 0);
        
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        $update = 0;

        // delete salesmtd (branchid, date) if exist
        try {
          //$this->logAction('DELETE', $backup->branchid.' '.$date->format('Y-m-d'));
          $this->salesmtdCtrl->deleteWhere(['branch_id'=>$backup->branchid, 'orddate'=>$date->format('Y-m-d')]);
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception($e->getMessage());    
        }

        $ds = [];
        $ds['slsmtd_totgrs'] = 0;
        $ds['date']       = $date->format('Y-m-d');
        $ds['branchid']   = $backup->branchid;
        
        for ($i=1; $i<=$record_numbers; $i++) {
          $row = dbase_get_record_with_names($db, $i);
          //$this->logAction('-', $row['ORDDATE']);

          try {
            $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
          } catch(Exception $e) {
            $vfpdate = $date->copy()->subDay();
          }

          if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
            $data = $this->salesmtdCtrl->associateAttributes($row);
            $data['branch_id'] = $backup->branchid;

            try {
              //$this->logAction($data['orddate'], ' create:salesmtd');
              $this->salesmtdCtrl->create($data);
              $update++;
            } catch(Exception $e) {
              dbase_close($db);
              throw new Exception('salesmtd: '.$e->getMessage());   
              return false;   
            }
            $ds['slsmtd_totgrs'] += $data['grsamt'];
          }
        }

        // update dailysales
        try {
          $this->ds->firstOrNew($ds, ['date', 'branchid']);
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception('salesmtd:ds: '.$e->getMessage());    
        }

        dbase_close($db);
        return $update;
      }
      return false;  
    }



    public function postCharges(Carbon $date, Backup $backup) {

      $dbf_file = $this->extracted_path.DS.'CHARGES.DBF';

      if (file_exists($dbf_file)) {
        //$this->logAction('posting', 'post:charges');
        $db = dbase_open($dbf_file, 0);
        
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        $update = 0;

        // delete charges (branchid, date) if exist
        try {
          //$this->logAction('DELETE', $backup->branchid.' '.$date->format('Y-m-d'));
          $this->charges->deleteWhere(['branch_id'=>$backup->branchid, 'orddate'=>$date->format('Y-m-d')]);
          } catch(Exception $e) {
          dbase_close($db);
          throw new Exception('charges: '.$e->getMessage());    
        }
        
        $ds = [];
        $ds['bank_totchrg'] = 0;
        $ds['chrg_total'] = 0;
        $ds['chrg_csh']   = 0;
        $ds['chrg_chrg']  = 0;
        $ds['chrg_othr']  = 0;
        $ds['disc_totamt']  = 0;
        $ds['date']       = $date->format('Y-m-d');
        $ds['branchid']   = $backup->branchid;
        

        for ($i=1; $i<=$record_numbers; $i++) {
          
          $row = dbase_get_record_with_names($db, $i);
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
          
          if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
            $data = $this->charges->associateAttributes($row);
            $data['branch_id'] = $backup->branchid;

            try {
              //$this->logAction($data['orddate'], ' create:charges');
              $this->charges->create($data);
              $update++;
              } catch(Exception $e) {
              dbase_close($db);
              throw new Exception('charges: '.$e->getMessage());  
              return false;  
            }

            switch (strtolower($data['terms'])) {
              case 'cash':
                $ds['chrg_csh'] += $data['tot_chrg'];
                break;
              case 'charge':
                $ds['chrg_chrg'] += $data['tot_chrg'];
                break;
              default:
                $ds['chrg_othr'] += $data['tot_chrg'];
                break;
            }
            $ds['chrg_total'] += $data['tot_chrg'];
            $ds['bank_totchrg'] += $data['bank_chrg'];
            $ds['disc_totamt']  += $data['disc_amt'];

          }
        }
        
        // update dailysales
        try {
          $this->ds->firstOrNew($ds, ['date', 'branchid']);
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception('charges:ds: '.$e->getMessage());    
        }

        dbase_close($db);
        return $update;
      }
      return false;
    }

   

    // prototype
    public function dbase($dbf) {
      $obj = new StdClass;
      $obj->filepath = $this->extracted_path.DS.$dbf;
      if (file_exists($obj->filepath)) {
        $obj->dbf = dbase_open($obj->filepath, 0);
        $obj->headers = dbase_get_header_info($obj->dbf);
        $obj->numrecords = dbase_numrecords($obj->dbf);
        dbase_close($obj->filepath);
        return $obj;
      }
      return false;
    }

  


    
}