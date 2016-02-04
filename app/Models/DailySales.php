<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class DailySales extends BaseModel {

	//protected $connection = 'boss';
	protected $table = 'dailysales';
	public $timestamps = false;
 	//protected $fillable = ['date', 'branchid', 'managerid', 'sales', 'cos', 'tips', 'custcount', 'empcount'];
	protected $guarded = ['id'];


	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  public function getDateAttribute($value){
    return Carbon::parse($value.' 00:00:00');
  }

	
	
 

 
	
  
}