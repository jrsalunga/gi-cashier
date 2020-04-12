<?php namespace App\Repositories\Kitlog;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class DayFoodRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['rank', 'ave'];

  public function model() {
    return 'App\\Models\\Kitlog\\DayFood';
  }
}