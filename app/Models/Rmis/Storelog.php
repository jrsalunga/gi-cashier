<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Storelog extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'storelog';
	protected $guarded = ['id'];

}
