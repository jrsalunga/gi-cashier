<?php namespace App\Models;

use App\Models\BaseModel;

class Prodcat extends BaseModel {

	protected $table = 'prodcat';
	//protected $guarded = ['id'];
	protected $fillable = ['code', 'descriptor'];

  public function products() {
    return $this->hasMany('App\Models\Product');
  }
 

 
	
  
}