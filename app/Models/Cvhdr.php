<?php namespace App\Models;

use App\Models\BaseModel;

class Cvhdr extends BaseModel {

	protected $table = 'cvhdr';
  public $timestamps = true;
  protected $dates = ['cvdate', 'checkdate', 'inv_fr', 'inv_to', 'updated_at', 'created_at'];
  protected $guarded = ['id'];
  
  public function psupplier() {
    return $this->belongsTo('App\Models\Psupplier');
  }

  public function bank() {
    return $this->belongsTo('App\Models\Bank');
  }
}
