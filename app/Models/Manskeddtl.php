<?php namespace App\Models;

use App\Models\BaseModel;
use Carbon\Carbon;

class Manskeddtl extends BaseModel {

	protected $table = 'manskeddtl';
	public $incrementing = false;
	public $timestamps = false;	
 	protected $fillable = ['mandayid', 'employeeid', 'daytype', 'timestart', 'breakstart', 'breakend', 'timeend', 'workhrs', 'breakhrs', 'loading'];
 	//public static $header = ['code', 'descriptor'];


  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    if (app()->environment()==='production')
      $this->setConnection('mysql-tk');
    else  
      $this->setConnection('tk-live');
  }
 

	public function manskedday() {
    return $this->belongsTo('App\Models\Manskedday', 'mandayid');
  }

  public function employee() {
    return $this->belongsTo('App\Models\Employee', 'employeeid');
  }



  /***************** mutators *****************************************************/
  /*
  public function getTimestartAttribute($value){
  		$value = $value=='off' ? '00:00':$value;
      return Carbon::parse(date('Y-m-d', strtotime('now')).' '.$value);
  }

   public function getBreakstartAttribute($value){
      return Carbon::parse(date('Y-m-d', strtotime('now')).' '.$value);
  }

  public function getBreakendAttribute($value){
      return Carbon::parse(date('Y-m-d', strtotime('now')).' '.$value);
  }

  public function getTimeendAttribute($value){
      return Carbon::parse(date('Y-m-d', strtotime('now')).' '.$value);
  }
  */

  /*
  * query scope for filtering mandtl where date on manday
  * Manskeddtl::whereEmployeeid($employeeid)->date($date);
  */
  public function scopeDate($query, $date){
    //$this->first()->manskedday()->where('date', '2015-11-13')->first();
    $query->leftJoin('manskedday','manskedday.id','=','manskeddtl.mandayid')
          ->select('manskeddtl.*', 'manskedday.date')
          ->where('manskedday.date','=', $date);
  }


  /*********   http://laravel.com/docs/eloquent#query-scopes    *******************/
  public function scopeEmployeeid($query, $employeeid) {
    return $query->whereEmployeeid($employeeid);
  }

  
  
}
