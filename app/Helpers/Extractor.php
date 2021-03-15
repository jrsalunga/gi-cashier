<?php namespace App\Helpers;

class Extractor
{
	protected $path;

	public function __construct($path=null) {
		if (!is_null($path))
			$this->setPath($path);
    else
      $this->path = storage_path();
	}

	public function setPath($path) {
		$this->path = $path;
	}

  public function getPath($path) {
    return $this->path
  }

	public function exists($filepath) {
		return $this->storage->exists($filepath);
	}

	public function realFullPath($path) {
		return $this->storage->realFullPath($path);
	}
}