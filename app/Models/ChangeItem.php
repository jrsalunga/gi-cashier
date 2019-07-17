<?php namespace App\Models;

use App\Models\BaseModel;

class ChangeItem extends BaseModel {

	protected $table = 'change_item';
	public $timestamps = false;
	//protected $appends = ['date'];
  protected $dates = ['date'];
 	//protected $fillable = ['date', 'cslipno', 'branch_id', 'fr_product_id', 'fr_qty', 'fr_price', 'to_product_id', 'to_qty', 'to_price', 'diff'];
	protected $guarded = ['id'];
	protected $casts = [
    'fr_price' 	=> 'float',
    'fr_qty' 		=> 'float',
    'to_qty' 		=> 'float',
    'to_price' 	=> 'float',
    'diff' 			=> 'float'
  ];

	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branch_id');
  }

  public function frProduct() {
    return $this->belongsTo('App\Models\Product', 'fr_product_id');
  }

  public function toProduct() {
    return $this->belongsTo('App\Models\Product', 'to_product_id');
  }
}