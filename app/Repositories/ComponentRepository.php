<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Repositories\Repository;
use App\Repositories\CompcatRepository as CompcatRepo;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;



class ComponentRepository extends BaseRepository implements CacheableInterface
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

  public function verifyAndCreate($data, $update=false) {
  	
    $compcat = $this->compcat->verifyAndCreate(array_only($data, ['supno', 'catname']));

    $attr = [
      // 'code' => 'new',
      'descriptor' => $data['comp'],
      'compcatid' => $compcat->id,
      'expenseid' => $compcat->expenseid,
      'cost' => $data['ucost'],
      'uom' => $data['unit'],
      // 'vat' => $data['vat'],
      // 'yield_pct' => $data['yield_pct'],
    ];

    if (isset($data['code']))
      $attr['code'] = $data['code'];

    if (isset($data['vat']))
      $attr['vat'] = $data['vat'];

    if (isset($data['yield_pct']))
      $attr['yield_pct'] = $data['yield_pct'];

    return $update
      ? $this->firstOrNewField($attr, ['descriptor'])
      : $this->findOrNew($attr, ['descriptor']);
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

  public function associateAttributes($r) {
    $row = [];

    $row = [
      'comp'      => trim($r['COMP']),
      'unit'      => trim($r['UNIT']),
      'ucost'     => trim($r['UCOST']),
      'vat'       => trim($r['VAT']),
      'yield_pct' => trim($r['YIELD_PCT']),

      'catname' => trim($r['CATNAME']),
      'supno'   => trim($r['SUPNO']),
    ];

    return $row;
  }




}