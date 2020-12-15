<?php namespace App\Models;

use App\Models\BaseModel;

class MonthSaleType extends BaseModel {

	protected $table = 'month_saletype';
	public $timestamps = false;
	//protected $appends = ['date'];
  protected $dates = ['date'];
	protected $guarded = ['id'];
	protected $casts = [
    'total' => 'float',
    'pct' => 'float'
  ];

  public function branch() {
    return $this->belongsTo('App\Models\Branch')->select(['code', 'descriptor', 'id']);
  }
}