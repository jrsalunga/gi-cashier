<?php namespace App\Models;

use App\Models\BaseModel;

class BossBranch extends BaseModel {

  //protected $connection = 'mysql-hr';
	protected $table = 'bossbranch';
 	protected $fillable = ['bossid', 'branchid'];


 	public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    
  }

	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  public function user() {
    return $this->belongsTo('App\User', 'bossid');
  }

  
  
}
