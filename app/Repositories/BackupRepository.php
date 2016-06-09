<?php namespace App\Repositories;
use DB;
use Carbon\Carbon;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use App\Repositories\Criterias\ByBranch;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;


//class BackupRepository extends BaseRepository implements CacheableInterface
class BackupRepository extends BaseRepository 
{
  //use CacheableRepository;

  public function boot(){
    $this->pushCriteria(new ByBranch(request()));
  }

	public function __construct() {
    parent::__construct(app());

      
  }


	public function model() {
    return 'App\\Models\\Backup';
  }


  public function dailyLogs($days = 7) {
  	$arr = [];
  	$to = Carbon::now();
  	//$to = Carbon::parse('2016-05-31');
  	$fr = $to->copy()->subDays($days);

  	$data = $this->aggregateDailyLogs($fr, $to);

  	for ($i=0; $i < $days; $i++) { 

  		$date = $to->copy()->subDays($i);

  		$filtered = $data->filter(function ($item) use ($date){
        return $item->filedate->format('Y-m-d') == $date->format('Y-m-d')
          ? $item : null;
    	});

  		array_push($arr, [ 
      		'date'=>$date,
      		'backup'=>$filtered->first()]
      );
  	}
  	/*
  	do {
  		
  		
      
      array_push($arr, [ 
      		'date'=>$date,
      		'backup'=>$filtered->first()]
      );

    } while ($date->addDay() < $to);
    */
    
    return $arr;
  }



  private function aggregateDailyLogs(Carbon $fr, Carbon $to) {
  	return $this->scopeQuery(function($query) use ($fr, $to) {
    	return $query
    						->select(DB::raw('*, count(*) as count'))
    						->whereBetween('filedate', [$fr->format('Y-m-d'), $to->format('Y-m-d')])
    						->groupBy(DB::raw('DAY(filedate)'))
    						->orderBy('filedate', 'DESC');
		})->all();
  }




  
  

    




}