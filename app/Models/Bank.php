<?php namespace App\Models;

use App\Models\BaseModel;

class Bank extends BaseModel {

	protected $table = 'bank';
  public $timestamps = true;
  protected $dates = ['updated_at', 'created_at'];
  protected $guarded = ['id'];
  
  public function cvhdr() {
    return $this->hasMany('\App\Models\Cvhdr');
  }
}
