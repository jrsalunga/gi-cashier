<?php namespace App\Traits;

use DB;
use Carbon\Carbon;

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



  public function firstOrNewField($attributes, $field) {
      
    $attr_idx = [];
    
    if (is_array($field)) 
      foreach ($field as $value) 
        $attr_idx[$value] = array_pull($attributes, $value);
    else 
      $attr_idx[$field] = array_pull($attributes, $field);

    $m = $this->model();
    $model = $m::firstOrNew($attr_idx);
    
    foreach ($attributes as $key => $value) 
      $model->{$key} = $value;
    
    return $model->save() ? $model : false;
  }

  public function deleteWhere(array $where){
    return $this->model->where($where)->delete();
  }

  public function sumFieldsByMonth($field, Carbon $date) {
    
    $select = '';
    $arr = [];
    if (is_array($field)) {
      foreach ($field as $key => $value) {
        $arr[$key] = 'sum('.$value.') as '.$value;
      }
      $select = join(',', $arr);
    } else {
      $select = 'sum('.$field.') as '.$field;
    }

    return $this
        ->skipCache()
        ->scopeQuery(function($query) use ($select, $date) {
          return $query->select(DB::raw($select))
            ->where(DB::raw('MONTH(date)'), $date->format('m'))
            ->where(DB::raw('YEAR (date)'), $date->format('Y'));
        })
        ->first();
  }


  public function sumFieldsByDate($field, Carbon $date, $branchid) {
    
    $select = '';
    $arr = [];
    if (is_array($field)) {
      foreach ($field as $key => $value) {
        $arr[$key] = 'sum('.$value.') as '.$value;
      }
      $select = join(',', $arr);
    } else {
      $select = 'sum('.$field.') as '.$field;
    }

    return $this
        //->skipCache()
        ->scopeQuery(function($query) use ($select, $date, $branchid) {
          return $query->select(DB::raw($select))
            ->where('date', $date->format('Y-m-d'))
            ->where('branchid', $branchid);
        })
        ->first();
  }


  public function rank($date=null, $field) {

    if ($date instanceof Carbon)
      return $this->generateRank($date, $field);
    elseif (is_iso_date($date))
      return $this->generateRank(c($date), $field);
    else
      return $this->generateRank(c(), $field);
  }

  public function generateRank(Carbon $date, $field) {
    
    $ms = $this->skipCache()
            ->scopeQuery(function($query) use ($date) {
              return $query->where(DB::raw('MONTH(date)'), $date->format('m'))
                          ->where(DB::raw('YEAR(date)'), $date->format('Y'));
            })
            ->orderBy($field, 'DESC')
            ->all();

    if (count($ms)<=0)
      return false;
    
    foreach ($ms as $key => $m) {
      //$this->update(['rank'=>($key+1)], $m->id);
      $m->rank = ($key+1);
      if ($m->{$field}>0)
        $m->save();

    }
    return true;
    
  }
  
}
