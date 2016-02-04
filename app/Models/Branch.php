<?php namespace App\Models;

use App\Models\BaseModel;

class Branch extends BaseModel {

  protected $connection = 'mysql-hr';
	protected $table = 'branch';
 	protected $fillable = ['code', 'descriptor'];
 	public static $header = ['code', 'descriptor'];

	public function employee() {
    return $this->hasMany('App\Models\Employee', 'employeeid');
  }

  public function holidays() {
    return $this->hasMany('App\Models\Holidaydtl', 'branchid');
  }

  public function dailysales() {
    return $this->hasMany('App\Models\DailySales', 'branchid');
  }





  /***************** mutators *****************************************************/
  /*
  public function getDescriptorAttribute($value){
      return ucwords(strtolower($value));
  }
  */


  public function getRouteKey()
{
    return $this->slug;
}
  
}
