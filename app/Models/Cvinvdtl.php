<?php namespace App\Models;

use App\Models\BaseModel;

class Cvinvdtl extends BaseModel {

	protected $table = 'cvinvdtl';
  public $timestamps = true;
  protected $dates = ['invdate', 'updated_at', 'created_at'];
  protected $guarded = ['id'];
  
  public function psupplier() {
    return $this->belongsTo('App\Models\Psupplier');
  }

  public function expense() {
    return $this->belongsTo('App\Models\Expense');
  }

  public function cvhdr() {
    return $this->belongsTo('App\Models\Cvhdr');
  }
}
