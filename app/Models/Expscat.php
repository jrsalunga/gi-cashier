<?php namespace App\Models;

use App\Models\BaseModel;

class Expscat extends BaseModel {

	protected $table = 'expscat';
  public $timestamps = false;
  //protected $appends = ['date'];
  //protected $dates = ['filedate'];
  //protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
  protected $guarded = ['id'];
  

	public function expenses() {
    return $this->hasMany('App\Models\Expense', 'expscatid');
  }

 
  
}
