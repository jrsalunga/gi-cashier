<?php namespace App\Models;

use App\Models\BaseModel;

class DayExpense extends BaseModel {

	protected $table = 'day_expense';
	public $timestamps = false;
	//protected $appends = ['date'];
  protected $dates = ['date'];
	protected $guarded = ['id'];
	protected $casts = [
    'tcost' => 'float',
    'qty' => 'float'
  ];

  public function expense() {
    return $this->belongsTo('App\Models\Expense');
  }

  public function branch() {
    return $this->belongsTo('App\Models\Branch')->select(['code', 'descriptor', 'id']);
  }

  
 

 
	
  
}