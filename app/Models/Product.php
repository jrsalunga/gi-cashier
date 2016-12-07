<?php namespace App\Models;

use App\Models\BaseModel;

class Product extends BaseModel {

  protected $table = 'product';
	//protected $guarded = ['id'];
	protected $fillable = ['code', 'descriptor', 'prodcat_id', 'menucat_id'];

  public function prodcat() {
    return $this->belongsTo('App\Models\Prodcat');
  }

  public function menucat() {
    return $this->belongsTo('App\Models\Menucat');
  }
}
