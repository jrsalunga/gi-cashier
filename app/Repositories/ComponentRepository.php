<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Repositories\Repository;
use App\Repositories\CompcatRepository as CompcatRepo;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;



class ComponentRepository extends BaseRepository implements CacheableInterface
//class ComponentRepository extends BaseRepository 
{
  use CacheableRepository;
  
  private $compcat;
	
	public function __construct(CompcatRepo $compcatrepo) {
    parent::__construct(app());
    $this->compcat = $compcatrepo;
     
  }


	public function model() {
    return 'App\Models\Component';
  }


  






  public function verifyAndCreate($data) {
  	
    $compcat = $this->compcat->verifyAndCreate(array_only($data, ['supno', 'catname']));

    $attr = [
      'code' => session('user.branchcode').'-new',
      'descriptor' => $data['comp'],
      'compcatid' => $compcat->id,
      'cost' => $data['ucost'],
      'uom' => $data['unit']
    ];

    return $this->firstOrNew($attr, 'descriptor');
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
  	// Retrieve by the attributes, or instantiate a new instance...
  	$model = $m::firstOrNew($attr_idx);
  	//$this->model->firstOrNew($attr_idx);
		
  	foreach ($attributes as $key => $value) {
  		$model->{$key} = $value;
  	}

  	return $model->save() ? $model : false;

  }




}