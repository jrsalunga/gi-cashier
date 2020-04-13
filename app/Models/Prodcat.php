<?php namespace App\Models;

use App\Models\BaseModel;

class Prodcat extends BaseModel {

	protected $table = 'prodcat';
	//protected $guarded = ['id'];
	protected $fillable = ['code', 'descriptor'];

  /* NOTE: this product should be on PRODUCT table for error-trapping/validation
  $product = [
    'code'        => 'XUN',
    'descriptor'  => 'UNKNOWN - NOT ON DATABASE',
    'menucat_id   => app()->environment('local') ? '11E7509A985A1C1B0D85A7E0C073910B' : 'A197E8FFBC7F11E6856EC3CDBB4216A7';,
    'prodcat_id   => app()->environment('local') ? '625E2E18BDF211E6978200FF18C615EC' : 'E841F22BBC3711E6856EC3CDBB4216A7',
    'id'          =>'11EA7D951C1B0D85A7E00911249AB5'
  ];
  */

  public function products() {
    return $this->hasMany('App\Models\Product');
  }
 

 
	
  
}