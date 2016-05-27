<?php namespace App\Models;

use App\Models\BaseModel;

class Expense extends BaseModel {

	protected $table = 'expense';
  public $timestamps = false;
  protected $guarded = ['id'];
  //protected $appends = ['date'];
  //protected $dates = ['filedate'];
  //protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
  

	public function compcats() {
    return $this->hasMany('App\Models\Compcat', 'expenseid');
  }

  public function expscat() {
    return $this->belongsTo('App\Models\Expscat', 'expscatid');
  }
  
}
