<?php namespace App\Repositories\Filters;

use App\Repositories\Filters\Filters;
use App\Repositories\Contracts\RepositoryInterface as Repository;
use Illuminate\Http\Request;

class ByBranch extends Filters {


    private $request;

    public function __construct(Request $request){
        $this->request = $request;
    }


    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, Repository $repository)
    {
        $model = $model->where('branchid', $this->request->user()->branchid);
        return $model;
    }
}