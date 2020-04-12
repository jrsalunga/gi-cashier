<?php namespace App\Models;

use App\Models\BaseModel;

class Statutory extends BaseModel {

	protected $table = 'statutory';
  protected $guarded = ['id'];
  protected $dates = ['date_reg'];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    if (app()->environment()==='production')
      $this->setConnection('mysql');
      
    // $this->setConnection('mysql-live');
  }

  public function branch() {
    return $this->belongsTo('App\Models\Employee');
  }
  
}
