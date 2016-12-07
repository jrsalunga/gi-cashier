<?php namespace App\Traits;

trait Repository {

  public function order($order=null, $asc='asc'){

    if(!is_null($order)) {
      if (is_array($order)) 
        foreach ($order as $key => $field) 
          $this->orderBy($field, $asc);
      else 
        $this->orderBy($field, $asc);
    } else {
      if (property_exists($this, 'order'))
        $this->order($this->order);
    }
    return $this;
  }


  /*****
  * search/match $field from $attributes and DB before creating 
  * @param: array attributes
  * @param: array/string field to be matched from attr and db 
  * @return: model
  * 
  */
  public function findOrNew($attributes, $field) {
    $attr_idx = [];

    if (is_array($field)) 
      foreach ($field as $value) 
        $attr_idx[$value] = array_get($attributes, $value);
    else 
      $attr_idx[$field] = array_get($attributes, $field);

    $obj = $this->findWhere($attr_idx)->first();

    return !is_null($obj) ? $obj : $this->create($attributes);
  }
  

  public function deleteWhere(array $where){
    return $this->model->where($where)->delete();
  }
  
}
