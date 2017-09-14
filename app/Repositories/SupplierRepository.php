<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;



class SupplierRepository extends BaseRepository implements CacheableInterface
//class SupplierRepository extends BaseRepository 
{
  use CacheableRepository;

	public function __construct() {
    parent::__construct(app());

     
  }


	public function model() {
    return 'App\Models\Supplier';
  }



  public function verifyAndCreate($data) {

    $attr = [
      'code' => substr($data['supno'], 2, 4),
      'descriptor' => $data['supname'],
      'tin' => $data['tin'],
      'branchid' => $data['branchid']
    ];

    //return $this->firstOrNew($attr, ['descriptor', 'branchid']);
    return $this->findOrNew($attr, ['descriptor', 'branchid']);
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
    if (is_null($obj->tin) && !is_null($attributes['tin']))
      $this->update(['tin'=>$attributes['tin']], $obj->id);


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