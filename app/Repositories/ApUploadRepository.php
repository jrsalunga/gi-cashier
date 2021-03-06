<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use App\Repositories\Criterias\ByBranch2;

class ApUploadRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;

 protected $order = ['created_at'];

  public function boot(){
    $this->pushCriteria(new ByBranch2(request()));
  }
  
  public function model() {
    return 'App\\Models\\ApUpload';
  }
}