<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Orderdtl extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'orderdtl';
	protected $guarded = ['id'];


	public function orderhdr() {
    return $this->belongsTo('App\Models\Rmis\Orderhdr', 'orderhdrid');
  }

  public function product() {
    return $this->belongsTo('App\Models\Rmis\Product', 'productid');
  }

	
  
}
