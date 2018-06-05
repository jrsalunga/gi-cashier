<?php namespace App\Repositories\Criterias;

use Prettus\Repository\Contracts\RepositoryInterface as Repository; 
use Prettus\Repository\Contracts\CriteriaInterface;
use Illuminate\Http\Request;

class ByBranch implements CriteriaInterface {

    private $request;

    public function __construct(Request $request){
        $this->request = $request;
        logAction('ByBranch', json_encode($request->all()));
    }

    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, Repository $repository)
    {
        $model = $model->where('branchid', $this->request->user()->branchid);
        //$model = $model->where(function($query){
        //    $query->where('branchid', $this->request->user()->branchid);
        //});
        return $model;
    }
}