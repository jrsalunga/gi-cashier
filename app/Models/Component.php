<?php namespace App\Models;

use App\Models\BaseModel;

class Component extends BaseModel {

	protected $table = 'component';
	public $timestamps = false;
	//protected $appends = ['date'];
  //protected $dates = ['filedate'];
 	//protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
	protected $guarded = ['id'];
	protected $casts = [
    'cost' => 'float'
  ];

	public function compcat() {
    return $this->belongsTo('App\Models\Compcat', 'compcatid');
  }

  
 

 
	
  
}