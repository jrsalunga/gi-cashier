<?php namespace App\Models;

use App\Models\BaseModel;

class Compcat extends BaseModel {

	protected $table = 'compcat';
  public $timestamps = false;
  //protected $appends = ['date'];
  //protected $dates = ['filedate'];
  //protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
  protected $guarded = ['id'];
  

	public function components() {
    return $this->hasMany('App\Models\Component', 'compcatid');
  }

  public function expense() {
    return $this->belongsTo('App\Models\Expense', 'expenseid');
  }
  
}
