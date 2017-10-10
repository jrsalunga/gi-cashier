<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Bankcard extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'bankcard';
	protected $guarded = ['id'];




	
  
}
