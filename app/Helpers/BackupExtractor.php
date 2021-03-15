<?php namespace App\Helpers;

use App\Helpers\Locator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class BackupExtractor
{
  protected $root_path;
  protected $locator;
  protected $extracting = false;
  protected $filepath = NULL;
  public $extracted_path = NULL;

  public function __construct($root_path=null, Locator $locator) {
    if (!is_null($root_path))
      $this->setRootPath($root_path);
    else
      $this->root_path = storage_path();

    $this->locator = $locator;
  }

  public function setRootPath($root_path) {
    return $this->root_path = $root_path;
  }

  public function getRootPath() {
    return $this->root_path;
  }

  public function setFilePath($dir=NULL) {
    if (!is_null($dir) || is_dir($dir))
      return $this->filepath = $dir;
  }

  public function getFilePath() {
    return $this->filepath;
  }

  public function getExtractedPath() {
    return $this->extracted_path;
  }

  public function is_open() {
    return $this->extracting ? true : false;
  }

  public function __toString() {
    return $this->extracting;
  }

  public function removeExtratedDir() {
    if (!is_null($this->extracted_path))
      return $this->removeDir($this->extracted_path);
    else
      return false;
  }

  public function clean() {
    if (!is_null($this->extracted_path))
      return $this->removeDir($this->extracted_path);
    else
      return false;
  }

  public function extract($brcode, $date, $pwd=NULL, $force_clean=false) {
    
    $date = is_carbon($date, true);

    /*try {
      $this->has_backup($brcode, $date);
    } catch (\Exception $e) {
      //throw $e;
      return $e->getMessage();
      return 0;
    }*/
    
    if ($this->has_backup($brcode, $date)) {


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
    $this->extracting = false;
    $this->extracted_path = NULL;
    //throw new \Exception('ZipArchive error code: '.$zip_status);
    return 0;
  }

  public function has_backup($brcode, $date) {
  
    $date = is_carbon($date, true);

    $locator = new Locator('pos');
    $d = backup_to_carbon_date('GC'.$date->format('mdy').'.ZIP');
    $path = $brcode.DS.$d->format('Y').DS.$d->format('m').DS.'GC'.$d->format('mdy').'.ZIP';
    if (!$locator->exists($path)) {
      //throw new \Exception('Backup '.$path.' do not exist.');
      // return 'Backup '.$path.' do not exist.';
      return 0;
    }

    $this->filepath = $locator->realFullPath($path);

    return 1;
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