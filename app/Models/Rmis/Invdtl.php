<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Invdtl extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'invdtl';
	protected $guarded = ['id'];




	public function invhdr() {
    return $this->belongsTo('App\Models\Rmis\Invhdr', 'invhdrid');
  }

  public function product() {
    return $this->belongsTo('App\Models\Rmis\Product', 'productid');
  }

  public function cancel() {
    return $this->belongsTo('App\Models\Rmis\Cancel', 'cancelid');
  }
  
}
