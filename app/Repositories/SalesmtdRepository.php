<?php namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;

class SalesmtdRepository extends BaseRepository implements CacheableInterface
//class SalesmtdRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['orddate', 'ordtime', 'recno'];

  public function model() {
    return 'App\\Models\\Salesmtd';
  }
  
	

}