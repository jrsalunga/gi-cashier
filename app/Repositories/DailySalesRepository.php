<?php namespace App\Repositories;

use App\Models\DailySales;
use App\Repositories\Filters\ByBranch;

use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;

class DailySalesRepository extends Repository 
{

	public function __construct(App $app, Collection $collection, ByBranch $byBranch) {
      parent::__construct($app, $collection);

      $this->pushFilters($byBranch);
  }


		public function model() {
      return 'App\Models\DailySales';
    }


    public function firstOrNew($attributes, $field) {
    	
    	$attr_idx = [];
    	
    	if (is_array($field)) {
    		foreach ($field as $value) {
    			$attr_idx[$value] = array_pull($attributes, $value);
    		}
    	} else {
    		$attr_idx[$field] = array_pull($attributes, $field);
    	}

    	$m = $this->model();
    	$model = $m::firstOrNew($attr_idx);
    	//$this->model->firstOrNew($attr_idx);
			
    	foreach ($attributes as $key => $value) {
    		$model->{$key} = $value;
    	}

    	return $model->save() ? $model : false;

    }


  public function lastRecord() {
  	$this->applyFilters();
    return $this->model->orderBy('date', 'DESC')->first();
  }




}