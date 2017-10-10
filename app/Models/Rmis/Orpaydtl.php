<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Orpaydtl extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'orpaydtl';
	protected $guarded = ['id'];
	protected $dates = ['date'];

	public function invhdr() {
    return $this->belongsTo('App\Models\Rmis\Invhdr', 'invhdrid');
  }

  public function bankcard() {
    return $this->belongsTo('App\Models\Rmis\Bankcard', 'bankcardid');
  }
  
}
