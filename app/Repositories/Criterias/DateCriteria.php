<?php namespace App\Repositories\Criterias; 

use Prettus\Repository\Contracts\RepositoryInterface; 
use Prettus\Repository\Contracts\CriteriaInterface;
use Auth;
use Carbon\Carbon;

class DateCriteria implements CriteriaInterface {

	protected $date;

	public function __construct(Carbon $date){
		$this->date = $date;
	}

  public function apply($model, RepositoryInterface $repository)
  {
      $model = $model->where('date', $this->date->format('Y-m-d'));
      return $model;
  }
}