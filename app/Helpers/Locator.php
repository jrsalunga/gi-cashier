<?php namespace App\Helpers;

use Carbon\Carbon;
use App\Repositories\DateRange;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;

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
}