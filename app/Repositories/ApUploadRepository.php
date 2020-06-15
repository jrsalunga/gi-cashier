<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;

class ApUploadRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;

  protected $order = ['descriptor'];
  
  public function model() {
    return 'App\\Models\\ApUpload';
  }
}