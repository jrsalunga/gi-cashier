<?php namespace App\Models;

use App\Models\BaseModel;

class Product extends BaseModel {

  protected $table = 'product';
	//protected $guarded = ['id'];
	protected $fillable = ['code', 'descriptor', 'prodcat_id', 'menucat_id', 'ucost', 'uprice', 'created_at'];
  protected $dates = ['created_at', 'updated_at'];

  /* NOTE: this product should be on PRODUCT table for error-trapping/validation
  $product = [
    'code'        => 'XUN',
    'descriptor'  => 'UNKNOWN - NOT ON DATABASE',
    'prodcat_id   => app()->environment('local') ? '625E2E18BDF211E6978200FF18C615EC' : 'E841F22BBC3711E6856EC3CDBB4216A7',
    'menucat_id   => app()->environment('local') ? '11E7509A985A1C1B0D85A7E0C073910B' : 'A197E8FFBC7F11E6856EC3CDBB4216A7';,
    'id'          =>'11EA7D951C1B0D85A7E00911249AB5'
  ];
  */

  public function prodcat() {
    return $this->belongsTo('App\Models\Prodcat');
  }

  public function menucat() {
    return $this->belongsTo('App\Models\Menucat');
  }

  public function getCreatedAtAttribute($timestamp) {
    return Carbon\Carbon::parse($timestamp)->format('Y-m-d H:i:s');
  }
}
