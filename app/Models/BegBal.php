<?php namespace App\Models;

use App\Models\BaseModel;

class BegBal extends BaseModel {

	protected $table = 'begbal';
	public $timestamps = false;
	//protected $appends = ['date'];
  protected $dates = ['date'];
	protected $guarded = ['id'];
	protected $casts = [
    'ucost' => 'float',
    'tcost' => 'float',
  ];

  public function branch() {
    return $this->belongsTo('App\Models\Branch')->select(['code', 'descriptor', 'id']);
  }

  public function Component() {
    return $this->belongsTo('App\Models\Component');
  }
}