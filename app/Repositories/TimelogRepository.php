<?php namespace App\Repositories;

use App\Models\Timelog;
use App\Repositories\Criterias\EmployeeByBranch;

use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;


//class TimelogRepository extends BaseRepository implements CacheableInterface
class TimelogRepository extends BaseRepository 
{
  //use CacheableRepository;

	public function __construct(App $app, Collection $collection, EmployeeByBranch $byBranch) {
      parent::__construct($app, $collection);

      $this->pushCriteria($byBranch);
  }


	public function model() {
    return 'App\\Models\\Timelog';
  }


  

    




}