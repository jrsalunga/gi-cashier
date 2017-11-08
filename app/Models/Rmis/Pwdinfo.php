<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Pwdinfo extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'pwdinfo';
	protected $guarded = ['id'];
	protected $dates = ['issuedate'];




	
  
}
