<?php namespace App\Repositories\Filters;

use App\Repositories\Filters\Filters;
use App\Repositories\Contracts\RepositoryInterface as Repository;

class Female extends Filters {


    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, Repository $repository)
    {
        $model = $model->where('gender', '2');
        return $model;
    }
}