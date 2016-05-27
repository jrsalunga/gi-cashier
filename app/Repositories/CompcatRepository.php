<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Repositories\Repository;
use App\Repositories\ExpenseRepository as ExpenseRepo;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;



class CompcatRepository extends BaseRepository implements CacheableInterface
//class CompcatRepository extends BaseRepository 
{
  use CacheableRepository;
  private $expense;
	public function __construct(ExpenseRepo $expenserepo) {
    parent::__construct(app());
    $this->expense = $expenserepo;
     
  }


	public function model() {
    return 'App\Models\Compcat';
  }



  public function verifyAndCreate($data) {
    //return $this->expense->firstOrNew(['code'=>substr($data['supno'], 0, 1)], 'code');
    $code = substr($data['supno'], 0, 2);
    $expense = $this->expense->firstOrNew(['code'=>$code], 'code');

    $attr = [
      'code' => $code.'-new',
      'descriptor' => $data['catname'],
      'expenseid' => $expense->id
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