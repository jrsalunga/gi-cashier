<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Product extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'product';
	protected $guarded = ['id'];


	public function prodcat() {
    return $this->belongsTo('App\Models\Rmis\Prodcat', 'prodcatid');
  }

  public function menuprods() {
    return $this->hasMany('App\Models\Rmis\Menuprod', 'productid');
  }

  public function menuprod() {
    return $this->hasOne('App\Models\Rmis\Menuprod', 'productid');
  }

  public function section() {
    return $this->belongsTo('App\Models\Rmis\Section', 'sectionid');
  }

  public function combos() {
    return $this->hasMany('App\Models\Rmis\Combo', 'comboid');
  }
  
}
