<?php namespace App\Models;

use App\Models\BaseModel;

class StockTransfer extends BaseModel {

	protected $table = 'stocktransfer';
  public $timestamps = false;
  protected $guarded = ['id'];

  public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  public function component() {
    return $this->belongsTo('App\Models\Component', 'componentid');
  }

  public function supplier() {
    return $this->belongsTo('App\Models\Supplier', 'supplierid');
  }

  public function toSupplier() {
    return $this->belongsTo('App\Models\Supplier', 'to');
  }

  public function toBranch() {
    return $this->belongsTo('App\Models\Branch', 'to');
  }
  
}
