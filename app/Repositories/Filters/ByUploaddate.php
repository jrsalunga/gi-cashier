<?php namespace App\Repositories\Filters;

use App\Repositories\Filters\Filters;
use App\Repositories\Contracts\RepositoryInterface as Repository;
use Illuminate\Http\Request;

class ByUploaddate extends Filters {



    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, Repository $repository)
    {
        $model = $model->orderBy('uploaddate', 'DESC');
        return $model;
    }
}