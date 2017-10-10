<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Prodcat extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'prodcat';
	protected $guarded = ['id'];

  public function products() {
    return $this->hasMany('App\Models\Rmis\Product', 'prodcatid');
  }
}
