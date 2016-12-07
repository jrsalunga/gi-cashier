<?php namespace App\Models;

use App\Models\BaseModel;

class Menucat extends BaseModel {

	protected $table = 'menucat';
	//protected $guarded = ['id'];
	protected $fillable = ['code', 'descriptor'];

  public function products() {
    return $this->hasMany('App\Models\Product');
  }
 

 
	
  
}