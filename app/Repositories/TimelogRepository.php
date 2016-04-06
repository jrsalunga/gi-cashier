<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Timelog;
use App\Repositories\Criterias\EmployeeByBranch;
use App\Repositories\Criterias\ByBranchCriteria;

use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\EmployeeRepository;


//class TimelogRepository extends BaseRepository implements CacheableInterface
class TimelogRepository extends BaseRepository 
{
  //use CacheableRepository;

  private $employees;

	public function __construct(EmployeeRepository $emprepo) {
    parent::__construct(app());

    $this->employees = $emprepo;

    $this->pushCriteria(new ByBranchCriteria(request()))
      ->scopeQuery(function($query){
      return $query->orderBy('datetime','desc');
    });
  }


	public function model() {
    return 'App\\Models\\Timelog';
  }



  public function whereBetween($field, $arr) {
  	$this->applyCriteria();
    $this->applyScope();


   return $this->model->whereBetween($field, $arr);
  }




  public function allTimelogByDate(Carbon $date) {

    return $this->scopeQuery(function($query) use ($date){
        return $query->whereBetween('datetime', [
                      $date->copy()->format('Y-m-d').' 06:00:00',          // '2015-11-13 06:00:00'
                      $date->copy()->addDay()->format('Y-m-d').' 05:59:59' // '2015-11-14 05:59:59'
                    ])
                  ->where('timelog.branchid', session('user.branchid'))
                  ->orderBy('timelog.datetime', 'ASC')
                  ->orderBy('timelog.txncode', 'ASC');
      });
  }


  public function allByDate(Carbon $date) {

    $arr = [];
    $timelogs = [];
    // get all timelog on the day/date
    $raw_timelogs = $this->allTimelogByDate($date)->all();
    //$raw_timelogs = ;

    $employees = $this->employees->with('position')
                      ->all(['code', 'lastname', 'firstname','gender','empstatus','positionid','deptid','branchid','id']);
    
    // timelog of employee assign to this branch
    $timelogs[0] = $raw_timelogs->filter(function ($item) use ($employees) {
      if(in_array($item->employeeid, $employees->pluck('id')->toArray()))
        return $item; 
    });

    $timelogs[1] = $raw_timelogs->filter(function ($item) use ($employees) {
      if(!in_array($item->employeeid, $employees->pluck('id')->toArray()))
        return $item; 
    });

    $col = collect($timelogs[0]);
    foreach ($employees as $key => $employee) {

      $arr[0][$key]['employee'] = $employee;
      
      for ($i=1; $i < 5; $i++) { 
        
        $arr[0][$key]['timelogs'][$i] = $col->where('employeeid', $employee->id)
                                        ->where('txncode', $i)
                                        ->sortBy('datetime')->first();
      }
      $arr[0][$key]['raw'] = $timelogs[0]->where('employeeid', $employee->id)
                            ->sortBy('datetime');
    }
    $arr[1] = $timelogs[1];


    return $arr;
    /*
    $filtered = $dss->filter(function ($item) use ($date){
          return $item->date->format('Y-m-d') == $date->format('Y-m-d')
                ? $item : null;
    */
  }


  

    




}