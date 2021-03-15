<?php namespace App\Helpers;

use Carbon\Carbon;
use App\Repositories\DateRange;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Locator 
{
	protected $storage;

	public function __construct($storage=null) {
		if (!is_null($storage))
			$this->setStorage($storage);
	}

	public function setStorage($storage) {
		$this->storage = new StorageRepository(new PhpRepository, $storage.'.'.app()->environment());
	}

	public function exists($filepath) {
		return $this->storage->exists($filepath);
	}

	public function realFullPath($path) {
		return $this->storage->realFullPath($path);
	}


  public function allFiles($dir='.') {

    $list = [];
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);
    
    foreach($files as $k => $file)
      if (!$file->isDir())
        array_push($list, $file->getRealPath());

    return $list;
  }

}