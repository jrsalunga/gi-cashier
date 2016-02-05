<?php namespace App\Repositories\Filters;

use App\Repositories\Contracts\RepositoryInterface as Repository;
use Illuminate\Http\Request;

abstract class Filters {

    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public abstract function apply($model, Repository $repository);
}