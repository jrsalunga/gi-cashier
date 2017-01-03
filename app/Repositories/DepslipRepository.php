<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;

class DepslipRepository extends BaseRepository implements CacheableInterface
//class MenucatRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;

  protected $order = ['created_at'];
  
  public function model() {
    return 'App\\Models\\Depslip';
  }


}