<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Orderhdr extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'orderhdr';
	protected $guarded = ['id'];


	public function orderdtl() {
    return $this->hasMany('App\Models\Rmis\Orderdtl', 'orderhdrid');
  }

  public function orderdtls() {
    return $this->hasMany('App\Models\Rmis\Orderdtl', 'orderhdrid');
  }

	
  
}
