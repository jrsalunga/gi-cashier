<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class Purchase extends BaseModel {


	protected $table = 'tpurchase';
	protected $guarded = ['id'];


  public function getDateAttribute($value){
    return Carbon::parse($value);
  }
 

 
	
  
}