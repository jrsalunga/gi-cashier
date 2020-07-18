<?php namespace App\Models;

use App\Models\BaseModel;

class Psupplier extends BaseModel {

	protected $table = 'psupplier';
  public $timestamps = true;
  protected $dates = ['updated_at', 'created_at'];
  protected $guarded = ['id'];
  

}
