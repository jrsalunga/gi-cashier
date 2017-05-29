<?php namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Manskedhdr;
use Auth;
use Carbon\Carbon;


class Manskedhdr extends BaseModel {

	protected $table = 'manskedhdr';
	public $incrementing = false;
	public $timestamps = false;	
 	protected $fillable = ['refno', 'date', 'branchid', 'managerid', 'mancost', 'year', 'weekno', 'notes'];
 	
 	//public static $header = ['code', 'descriptor'];

 
  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    if (app()->environment()==='production')
      $this->setConnection('mysql-tk');
    else  
      $this->setConnection('tk-live');
  }

  

 	
  /***************** relations *****************************************************/
 	public function manskeddays() {
 		return $this->hasMany('App\Models\Manskedday', 'manskedid');
 	}

	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  public function manager() {
    return $this->belongsTo('App\Models\Employee', 'managerid');
  }


  /***************** mutators *****************************************************/
  public function getDateAttribute($value){
      return Carbon::parse($value);
  }


  /*********   http://laravel.com/docs/eloquent#query-scopes    *******************/
  public function scopeBranchid($query, $branchid) {
    return $query->whereBranchid($branchid);
  }

  public function scopeWeekno($query, $weekno) {
    return $query->whereWeekno($weekno);
  }
  


  /***************** over ride base model *****************************************************/
  public function next($field=null){
    if($this->lastWeekOfYear() > $this->weekno){
      $res = $this->query()->where('branchid', $this->branchid)
                        ->where('weekno', '>', $this->weekno)
                        ->where('year', '=', $this->year)
                        ->orderBy('year', 'ASC')
                        ->orderBy('weekno', 'ASC')->get()->first();
    } else {
      $res = $this->query()->where('branchid', $this->branchid)
                        ->where('year', '>', $this->year)
                        ->orderBy('year', 'ASC')
                        ->orderBy('weekno', 'ASC')->get()->first();
    }
    return $res==null ? 'false':$res;
  }



  public function filledManday(){
    $res = $this->query()->select(\DB::raw('count(manskedday.empcount) as filled'))
                          ->join('manskedday', function($join){
                            $join->on('manskedday.manskedid', '=', 'manskedhdr.id')
                                ->where('manskedhdr.branchid', '=', session('user.branchid'));
                            })
                            ->where('manskedhdr.id', $this->id)->get()->first();
    return $res==null ? 0:$res->filled;
  }

  public function previous($field=null){
    if(1 < $this->weekno){
      $res = $this->query()->where('branchid', $this->branchid)
                        ->where('weekno', '<', $this->weekno)
                        ->where('year', '=', $this->year)
                        ->orderBy('year', 'DESC')
                        ->orderBy('weekno', 'DESC')->get()->first();
    } else {
      $res = $this->query()->where('branchid', $this->branchid)
                        ->where('weekno', '>', $this->weekno)
                        ->where('year', '<', $this->year)
                        ->orderBy('year', 'DESC')
                        ->orderBy('weekno', 'DESC')->get()->first();
    }
    return $res==null ? 'false':$res;
  }

  /***************** misc func *****************************************************/
  public function weekNo($weekno=null){
    if(empty($weekno) || $weekno==null)
      return str_pad($this->$weekno,2,'0',STR_PAD_LEFT);
    else 
      return str_pad($weekno,2,'0',STR_PAD_LEFT);
  }




/*
  generate new week info for branch
  function for route: /task/mansked
  @param: branch id
  @return: array 
*/
  public function newWeek($branchid = null){
    $arr = [];
    //$obj = $this->query()->where('branchid', $branchid)->orderBy('created_at', 'DESC')->get()->first();
    $obj = $this->query()->where('branchid', $branchid)->orderBy('year', 'DESC')->orderBy('weekno', 'DESC')->get()->first();
    if(count($obj) <= 0){
      $arr['weekno'] = date('W', strtotime('now'));
      $arr['year'] = date('Y', strtotime('now'));
      $arr['weekdays'] = $this->getDaysByWeekNo($arr['weekno']);
    } else {
      if($this->lastWeekOfYear() > $obj->weekno){
        $arr['weekno'] = $obj->weekno+1;
        $arr['year'] = $obj->year;
        $arr['weekdays'] = $this->getDaysByWeekNo($obj->weekno+1);
        $arr['lmanskedid'] = $obj->id;
      } else {
        $arr['weekno'] = 1;
        $arr['year'] = Carbon::now()->addYear()->year;
        $arr['weekdays'] = $this->getDaysByWeekNo(1, $arr['year']);
        $arr['lmanskedid'] = $obj->id;
      }
    }
    return $arr;
  }

/*
  get days of the week
  @param: week number, year
  @return: array of days of week in Carbon instance
*/
  public function getDaysByWeekNo($weekno='', $year=''){
  	$weekno = (empty($weekno) || $weekno > 53) ? date('W', strtotime('now')) : $weekno;
  	$year = empty($year) ?  date('Y', strtotime('now')) : $year;
		for($day=1; $day<=7; $day++) {
		    $arr[$day-1] = Carbon::parse(date('Y-m-d', strtotime($year."W".$this->weekNo($weekno).$day)));
		}
		return $arr;
  }


  public static function getWeeks($year) {
  	$mw = Manskedhdr::select('weekno')->where(\DB::raw('YEAR(date)'), '=',  $year)->get();
  	$m = $mw->pluck('weekno')->toArray();
  	
  	for($week_ctr = 0; $week_ctr <= (date("W", mktime(0,0,0,12,28,$year))+1);  $week_ctr++){
			//array_push($weeks, $week_ctr+1);
			$weeks[$week_ctr]['week'] = $week_ctr + 1;;
			$weeks[$week_ctr]['created'] = in_array($week_ctr, $m) ? 'yes':'no';
		}
		return $weeks;
  }

  public static function getManskedday($year, $weekno){

  	$mansked = Manskedhdr::with('manskeddays')
  												->where('weekno', $weekno)
  												->where('branchid', Auth::user()->branchid)
  												->get()->first();

    

		$days = isset($mansked) ? $mansked->manskeddays->keyBy('date')->toArray():[];
	
  	$arr_days = [];
  	for($day=0; $day<=7; $day++) {
  		if(!$day==0){
  			
  			$currday = date('Y-m-d', strtotime($year."W".$weekno.$day));
	     	$arr_days[$day]['date'] = $currday;
	     	$arr_days[$day][0] = $currday;
	     	
	     	if(array_key_exists($currday, $days)){
	     		$arr_days[$day]['created'] = 'true';
	     		$arr_days[$day][1] = 'true';
	     		$x = 2;
	     		foreach ($days[$currday] as $key => $value) {
	     			if($key=='date')
	     				continue;
	     			$arr_days[$day][$key] = $value;
	     			$arr_days[$day][$x] = $value;
	     			$x++;
	     		}

	     	} else {
	     		// no data from database
	     		$arr_days[$day]['created'] = 'false';
	     		$arr_days[$day][1] = 'false';
	     		$arr_days[$day][2] = '-';
	     		$arr_days[$day][3] = '-';
	     		$arr_days[$day][4] = '-';
	     		$arr_days[$day][5] = '-';
	     		$arr_days[$day][6] = '-';
	     		$arr_days[$day][7] = 'Create Header';
	     		
	     	}
	     	$arr_days[$day][7] = $arr_days[$day][1];
  		} else {
  			$arr_days[0]['created'] = '';
  			$arr_days[0][0] = '<i class="fa fa-calendar"></i> Date';
				$arr_days[0][1] = 'created';
     		$arr_days[0][2] = '';
     		$arr_days[0][3] = '<span class="glyphicon glyphicon-user"></span> Customers';
     		$arr_days[0][4] = '<i class="fa fa-line-chart"></i> Ave Spending';
     		$arr_days[0][5] = '<i class="fa fa-users"></i> Crew on Duty';
     		$arr_days[0][6] = '';
     		$arr_days[0][7] = '';
     		
  		}
  		

		}

		return $arr_days;


  }



  public static function paginateWeeks(Request $request, $year='2015', $limit=10) {
  	$weeks = self::getWeeks($year);
  	$page = !empty($request->input('page')) ? $request->input('page'):1;
  	$offset = (intval($page) - 1) * $limit;
  	//$paginator = new Paginator(array_slice($weeks, $offset), $limit);
  	//dd(Collection::make($weeks));
  	$sliced = array_slice($weeks, $offset, $limit);
  	$paginator = new Paginator($sliced, count($weeks), $limit);
  	$paginator->setPath('mansked');
  	
  	return $paginator;

  }

 




  
}
