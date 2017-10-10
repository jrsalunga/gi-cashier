<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Combo extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'combo';
	protected $guarded = ['id'];


	public function product() {
    return $this->belongsTo('App\Models\Rmis\Product', 'productid', 'id');
  }

	
  
}
