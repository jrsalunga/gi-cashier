<?php namespace App\Models;

use App\Models\BaseModel;

class Company extends BaseModel {

	protected $connection = 'mysql-hr';
	protected $table = 'company';
  protected $guarded = ['id'];
  

	public function branches() {
    return $this->hasMany('App\Models\Branch', 'companyid');
  }

  
  
}
