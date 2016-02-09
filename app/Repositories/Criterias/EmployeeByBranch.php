<?php namespace App\Repositories\Criterias;

use Prettus\Repository\Contracts\RepositoryInterface as Repository; 
use Prettus\Repository\Contracts\CriteriaInterface;
use Illuminate\Http\Request;

class EmployeeByBranch implements CriteriaInterface {


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
        $model = $model->leftJoin('hr.employee', 'timelog.employeeid', '=', 'employee.id')
                ->where('employee.branchid', $this->request->user()->branchid)
                ->select('timelog.*')
                ->orderBy('timelog.datetime', 'DESC');
        return $model;
    }
}