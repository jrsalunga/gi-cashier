<?php namespace App\Models;

use App\Models\BaseModel;

class Supplier extends BaseModel {

	protected $table = 'supplier';
  public $timestamps = false;
  protected $guarded = ['id'];

  public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }
  
}
