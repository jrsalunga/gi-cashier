<?php namespace App\Models;

use App\Models\BaseModel;

class Check extends BaseModel {

	protected $table = 'check';
  public $timestamps = true;
  protected $dates = ['date', 'updated_at', 'created_at'];
  protected $guarded = ['id'];
  
  public function cvhdr() {
    return $this->belongsTo('\App\Models\Cvhdr');
  }

  public function bank() {
    return $this->belongsTo('\App\Models\Bank');
  }
}
