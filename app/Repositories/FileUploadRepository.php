<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use App\Repositories\Criterias\ByBranch;

class FileUploadRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;

  protected $order = ['uploaddate'];

  public function boot(){
    $this->pushCriteria(new ByBranch(request()));
  }
  
  public function model() {
    return 'App\\Models\\FileUpload';
  }




  
	

}