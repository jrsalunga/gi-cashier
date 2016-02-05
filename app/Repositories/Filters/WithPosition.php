<?php namespace App\Repositories\Filters;

use App\Repositories\Filters\Filters;
use App\Repositories\Contracts\RepositoryInterface as Repository;
use Illuminate\Http\Request;

class WithPosition extends Filters {


    private $columns;

    public function __construct($columns = array('*')){
        $this->columns = $columns;
    }


    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, Repository $repository)
    {
        $model = $model->with(['position'=>function($query){
            $query->select($this->columns);
        }]);
        return $model;
    }
}