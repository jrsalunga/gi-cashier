<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Menuprod extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'menuprod';
	protected $guarded = ['id'];

	public function menucat() {
    return $this->belongsTo('App\Models\Rmis\Menucat', 'menucatid');
  }


	
  
}
