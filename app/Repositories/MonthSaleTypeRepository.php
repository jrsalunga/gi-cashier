<?php namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class MonthSaleTypeRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['month', 'ordinal'];

  public function model() {
    return 'App\\Models\\MonthSaleType';
  }


  

  
  
	

}