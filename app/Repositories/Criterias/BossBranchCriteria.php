<?php namespace App\Repositories\Criterias; 

use Prettus\Repository\Contracts\RepositoryInterface; 
use Prettus\Repository\Contracts\CriteriaInterface;
use Auth;

class BossBranchCriteria implements CriteriaInterface {

  public function apply($model, RepositoryInterface $repository)
  {
      $model = $model->where('bossid','=', Auth::user()->id );
      return $model;
  }
}