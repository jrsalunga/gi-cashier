<?php namespace App\Repositories\Criterias; 

use Prettus\Repository\Contracts\RepositoryInterface; 
use Prettus\Repository\Contracts\CriteriaInterface;
use Auth;

class BranchDailySalesCriteria implements CriteriaInterface {

  public function apply($model, RepositoryInterface $repository)
  {
      //$branchids = $repository->bossbranch->all()->pluck('branchid');
      $model = $model->whereIn('branchid', ['F8056E535D0B11E5ADBC00FF59FBB323']);
      return $model;
  }
}