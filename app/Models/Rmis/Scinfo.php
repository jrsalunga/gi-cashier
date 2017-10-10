<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Scinfo extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'scinfo';
	protected $guarded = ['id'];
	protected $dates = ['issuedate'];




	
  
}
