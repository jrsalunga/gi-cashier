<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Invhdr extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'invhdr';
	protected $guarded = ['id'];
	protected $dates = ['date'];



	public function invdtls() {
    return $this->hasMany('App\Models\Rmis\Invdtl', 'invhdrid');
  }

  public function scinfos() {
    return $this->hasMany('App\Models\Rmis\Scinfo', 'invhdrid');
  }	
  
}
