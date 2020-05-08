<?php namespace App\Models\Kitlog;

use App\Models\BaseModel;

class DatasetArea extends BaseModel {

	protected $table = 'dataset_area';
  protected $dates = ['date'];
	protected $guarded = ['id'];
}