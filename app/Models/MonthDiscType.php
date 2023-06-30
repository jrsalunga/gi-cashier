<?php namespace App\Models;

use App\Models\BaseModel;

class MonthDiscType extends BaseModel {

	protected $table = 'month_disctype';
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