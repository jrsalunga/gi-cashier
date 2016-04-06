<?php namespace App\Repositories\Criterias; 

use Illuminate\Http\Request;
use Prettus\Repository\Contracts\RepositoryInterface; 
use Prettus\Repository\Contracts\CriteriaInterface;

class ByBranchCriteria implements CriteriaInterface {

	private $request;

  public function __construct(Request $request){
      $this->request = $request;
  }

  public function apply($model, RepositoryInterface $repository)
  {
      //$branchids = $repository->bossbranch->all()->pluck('branchid');
      $model = $model->where('branchid', $this->request->user()->branchid);
      return $model;
  }
}