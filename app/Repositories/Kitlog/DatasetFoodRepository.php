<?php namespace App\Repositories\Kitlog;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;

class DatasetFoodRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  public function model() {
    return 'App\\Models\\Kitlog\\DatasetFood';
  }
}