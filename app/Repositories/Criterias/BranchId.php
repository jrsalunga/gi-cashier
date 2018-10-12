<?php namespace App\Repositories\Criterias;

use Prettus\Repository\Contracts\RepositoryInterface as Repository; 
use Prettus\Repository\Contracts\CriteriaInterface;
use Illuminate\Http\Request;
use App\Models\Branch;

class BranchId implements CriteriaInterface {

    private $request;

    public function __construct($branch){
        if ($branch instanceof Branch)
            $this->id = $branch->id;
        else
            $this->id = $branchid;
    }

    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, Repository $repository)
    {
        if (\Schema::hasColumn($model->getTable(), 'branchid')) {

            return $model->where(function($query) {
                $query->where('branchid', $this->id);
            });
        }

        if (\Schema::hasColumn($model->getTable(), 'branch_id')) {
            
            return $model->where(function($query) {
                $query->where('branch_id', $this->id);
            });
        }
    }
}