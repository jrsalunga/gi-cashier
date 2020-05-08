<?php namespace App\Models\Kitlog;

use App\Models\BaseModel;

class DatasetFood extends BaseModel {

	protected $table = 'dataset_food';
  protected $dates = ['date'];
	protected $guarded = ['id'];

  public function product() {
    return $this->belongsTo('App\Models\Product');
  }
}