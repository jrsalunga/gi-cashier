<?php namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Criterias\ByBranchCriteria;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;


class EmployeeRepository extends BaseRepository implements CacheableInterface
//class EmployeeRepository extends BaseRepository 
{
  use CacheableRepository;


  public function __construct() {
      parent::__construct(app());

      $this->pushCriteria(new ByBranchCriteria(request()))
      ->scopeQuery(function($query){
        return $query->orderBy('lastname')->orderBy('firstname');
      });
  }

	public function model() {
    return 'App\\Models\\Employee';
  }

  public function findOrWhere( array $where , $columns = array('*'))
  {
    $this->applyCriteria();
    $this->applyScope();

    foreach ($where as $field => $value) {
        if ( is_array($value) ) {
            list($field, $condition, $val) = $value;
            $this->model = $this->model->orWhere($field,$condition,$val);
        } else {
            $this->model = $this->model->orWhere($field,'=',$value);
        }
    }

    $model = $this->model->get($columns);
    $this->resetModel();

    return $this->parserResult($model);
  }

  public function where($where, $columns = ['*'], $or = true)
  {
    $this->applyCriteria();

    $model = $this->model;

    foreach ($where as $field => $value) {
      if ($value instanceof \Closure) {
          $model = (! $or)
              ? $model->where($value)
              : $model->orWhere($value);
      } elseif (is_array($value)) {
          if (count($value) === 3) {
              list($field, $operator, $search) = $value;
              $model = (! $or)
                  ? $model->where($field, $operator, $search)
                  : $model->orWhere($field, $operator, $search);
          } elseif (count($value) === 2) {
              list($field, $search) = $value;
              $model = (! $or)
                  ? $model->where($field, '=', $search)
                  : $model->orWhere($field, '=', $search);
          }
      } else {
          $model = (! $or)
              ? $model->where($field, '=', $value)
              : $model->orWhere($field, '=', $value);
      }
    }
    return $model->get($columns);
  }

  public function andOrWhere($where, $columns = ['*'], $or = true)
  {
    $this->applyCriteria();

    $model = $this->model;

    foreach ($where as $field => $value) {
      if ($value instanceof \Closure) {
          $model = (! $or)
              ? $model->where($value)
              : $model->orWhere($value);
      } elseif (is_array($value)) {
          if (count($value) === 3) {
              list($field, $operator, $search) = $value;
              $model->where($field, $operator, $search);
              $model = (! $or)
                  ? $model->where($field, $operator, $search)
                  : $model->orWhere($field, $operator, $search);
          } elseif (count($value) === 2) {
              list($field, $search) = $value;
              $model = (! $or)
                  ? $model->where($field, '=', $search)
                  : $model->orWhere($field, '=', $search);
          }
      } else {
          $model = (! $or)
              ? $model->where($field, '=', $value)
              : $model->orWhere($field, '=', $value);
      }
    }
    return $model->get($columns);
  }


    




}