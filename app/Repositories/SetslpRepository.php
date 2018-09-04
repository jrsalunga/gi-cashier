<?php namespace App\Repositories;
use DB;
use Exception;
use Carbon\Carbon;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use App\Repositories\Criterias\ByBranch2;

class SetslpRepository extends BaseRepository implements CacheableInterface
//class MenucatRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;

  protected $order = ['created_at'];

  public function boot(){
    $this->pushCriteria(new ByBranch2(request()));
  }
  
  public function model() {
    return 'App\\Models\\Setslp';
  }



  private function aggregateDailyLogs(Carbon $fr, Carbon $to) {
  	return $this->scopeQuery(function($query) use ($fr, $to) {
    	return $query
    						->whereBetween('date', 
    							[$fr->format('Y-m-d').' 10:00:00', $to->copy()->addDay()->format('Y-m-d').' 09:59:59']
    							)
    						->orderBy('created_at', 'DESC');
    						//->orderBy('filedate', 'DESC');
		})->skipCache()->all();
  }




	public function monthlyLogs(Carbon $date) {
    $arr = [];
    $fr = $date->firstOfMonth();
    $to = $date->copy()->lastOfMonth();

    $data = $this->aggregateDailyLogs($fr, $to);

    for ($i=0; $i < $date->daysInMonth; $i++) { 

      $date = $fr->copy()->addDays($i);
  	
      $arr[$i]['date'] = $date;
      $arr[$i]['total'] = 0;

      $filtered = $data->filter(function ($item) use ($date){
        $s = c($date->format('Y-m-d').' 10:00:00');
        $e = c($date->copy()->addDay()->format('Y-m-d').' 09:59:59');

        return $item->date->gte($s) && $item->date->lte($e)
          ? $item : null;
      });

      $arr[$i]['count'] = count($filtered);

      $arr[$i]['datas'] = $filtered;

      if (count($filtered)>0) 
        foreach ($filtered as $key => $obj) 
          $arr[$i]['total'] += $obj->amount;

  		
  		
  	}

    
    return $arr;
  }


}