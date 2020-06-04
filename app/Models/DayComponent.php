<?php namespace App\Models;

use App\Models\BaseModel;

class DayComponent extends BaseModel {

	protected $table = 'day_component';
	public $timestamps = false;
	//protected $appends = ['date'];
  protected $dates = ['date'];
	protected $guarded = ['id'];
	protected $casts = [
    'tcost' => 'float',
    'qty' => 'float'
  ];

	public function component() {
    return $this->belongsTo('App\Models\Component');
  }

  public function expense() {
    return $this->belongsTo('App\Models\Expense');
  }

  public function branch() {
    return $this->belongsTo('App\Models\Branch')->select(['code', 'descriptor', 'id']);
  }

  
 

 
	
  
}