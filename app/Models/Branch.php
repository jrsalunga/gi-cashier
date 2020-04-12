<?php namespace App\Models;

use App\Models\BaseModel;
use Carbon\Carbon;

class Branch extends BaseModel {

  protected $connection = 'mysql-hr';
	protected $table = 'branch';
 	protected $fillable = ['code', 'descriptor', 'opendate', 'email', 'mancost', 'address'];
 	public static $header = ['code', 'descriptor'];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    if (app()->environment()==='production')
      $this->setConnection('mysql-hr');
    $this->setConnection('hr');
  }

	public function employee() {
    return $this->hasMany('App\Models\Employee', 'employeeid');
  }

  public function holidays() {
    return $this->hasMany('App\Models\Holidaydtl', 'branchid');
  }

  public function dailysales() {
    return $this->hasMany('App\Models\DailySales', 'branchid');
  }

  public function opendate() {
    return Carbon::parse($this->opendate);
  }

  public function branch_to() {
    return $this->hasOne('App\Models\EmploymentActivity', 'to_branch_id');
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
