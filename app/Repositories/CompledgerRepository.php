<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Repositories\Repository;
use App\Repositories\CompcatRepository as CompcatRepo;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;



class CompledgerRepository extends BaseRepository implements CacheableInterface
//class CompledgerRepository extends BaseRepository 
{
  use CacheableRepository;
  
 
	
	public function __construct(CompcatRepo $compcatrepo) {
    parent::__construct(app());
    $this->compcat = $compcatrepo;
     
  }


	public function model() {
    return 'App\Models\Compledger';
  }


  






  

  public function findOrNew($attributes, $field) {

    $attr_idx = [];

    if (is_array($field)) {
      foreach ($field as $value) {
        $attr_idx[$value] = array_get($attributes, $value);
      }
    } else {
      $attr_idx[$field] = array_get($attributes, $field);
    }

    $obj = $this->findWhere($attr_idx)->first();

    return !is_null($obj) ? $obj : $this->create($attributes);
  }


  public function firstOrNewField($attributes, $field) {
    	
  	$attr_idx = [];
  	
  	if (is_array($field)) {
  		foreach ($field as $value) {
  			$attr_idx[$value] = array_pull($attributes, $value);
  		}
  	} else {
  		$attr_idx[$field] = array_pull($attributes, $field);
  	}

  	$m = $this->model();
  	// Retrieve by the attributes, or instantiate a new instance...
  	$model = $m::firstOrNew($attr_idx);
  	//$this->model->firstOrNew($attr_idx);
		
  	foreach ($attributes as $key => $value) {
  		$model->{$key} = $value;
  	}

  	return $model->save() ? $model : false;

  }




}