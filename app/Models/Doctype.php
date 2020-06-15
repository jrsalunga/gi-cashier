<?php namespace App\Models;

use App\Models\BaseModel;

class Doctype extends BaseModel {

	protected $table = 'doctype';
	public $timestamps = true;
	protected $guarded = ['id'];
  protected $dates = ['created_at', 'updated_at'];
}