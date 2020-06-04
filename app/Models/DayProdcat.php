<?php namespace App\Models;

use App\Models\BaseModel;

class DayProdcat extends BaseModel {

  protected $table = 'day_prodcat';
  public $timestamps = false;
  //protected $appends = ['date'];
  protected $dates = ['date'];
  protected $guarded = ['id'];
  protected $casts = [
    'sales' => 'float',
    'qty' => 'float',
    'trans' => 'integer',
    'pct' => 'float'
  ];

  public function prodcat() {
    return $this->belongsTo('App\Models\Prodcat');
  }

  public function branch() {
    return $this->belongsTo('App\Models\Branch')->select(['code', 'descriptor', 'id']);
  }

  
 

 
  
  
}