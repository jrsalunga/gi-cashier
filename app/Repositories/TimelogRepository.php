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
use App\Helpers\Timesheet;


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
                  ->where('ignore', 0)
                  ->orderBy('timelog.datetime', 'ASC')
                  ->orderBy('timelog.txncode', 'ASC');
      });
  }

  public function generateTimesheet($employeeid, Carbon $date, $timelogs) {
    $ts = new Timesheet;
    return $ts->generate($employeeid, $date, $timelogs);
  }

  public function getActiveEmployees($field = NULL) {
    $field = !is_null($field) ? $field : ['code', 'lastname', 'firstname','gender','empstatus','positionid','deptid','branchid','id'];
    return $this->employees->with('position')
                ->orderBy('lastname')
                ->orderBy('firstname')
                ->findWhereNotIn('empstatus', [4, 5], $field);
  }

  public function allByDate(Carbon $date) {

    $arr = [];
    $timelogs = [];
    // get all timelog on the day/date
    $raw_timelogs = $this->allTimelogByDate($date)->all();
    //$raw_timelogs = ;
    $tk_empids =  $raw_timelogs->pluck('employeeid')->toArray();
    $employees = $this->getActiveEmployees();

    $br_empids = $employees->pluck('id')->toArray();
    $combined_empids = collect($tk_empids)->merge($br_empids)->unique()->values()->all();

    $o = [];
    foreach ($combined_empids as $key => $id) {
      $o[$key] = $this->employees
            ->skipCriteria()
            ->findByField('id', $id, ['code', 'lastname', 'firstname', 'id'])
            ->first()->toArray();
    }

    $sorted_emps = collect($o)->sortBy('firstname')->sortBy('lastname');

    $col = collect($raw_timelogs);
    foreach (array_values($sorted_emps->toArray()) as $key => $emp) {
     
      $e = $this->employees
            ->skipCriteria()
            ->findByField('id', $emp['id'], ['code', 'lastname', 'firstname', 'id', 'positionid'])
            ->first();
      
      $arr[0][$key]['employee'] = $e;
      $arr[0][$key]['onbr'] = in_array($emp['id'], $br_empids) ? true : false; // on branch??

      for ($i=1; $i < 5; $i++) { 
        $arr[0][$key]['timelogs'][$i] = $col->where('employeeid', $emp['id'])
                                            ->where('txncode', $i)
                                            ->sortBy('datetime')
                                            ->first();
      }
      
      $raw = $raw_timelogs->where('employeeid', $e->id)->sortBy('datetime');
      
      $arr[0][$key]['timesheet'] = $this->generateTimesheet($e->id, $date, $raw);

      $arr[0][$key]['raw'] = $raw;
    }
    $arr[1] = [];
    return $arr;
    
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
                                            ->sortBy('datetime')
                                            ->first();
      }
      
      $raw = $timelogs[0]->where('employeeid', $employee->id)->sortBy('datetime');
      
      $arr[0][$key]['timesheet'] = $this->generateTimesheet($employee->id, $date, $raw);

      $arr[0][$key]['raw'] = $raw;
    
    }
    $arr[1] = $timelogs[1];
    


    return $arr;
    /*
    $filtered = $dss->filter(function ($item) use ($date){
          return $item->date->format('Y-m-d') == $date->format('Y-m-d')
                ? $item : null;
    */
  }



  public function getRawEmployeeTimelog($employeeid, Carbon $fr, Carbon $to) {

      return $this->scopeQuery(function($query) use ($employeeid, $fr, $to) {
        return $query->where('employeeid', $employeeid)
                    ->whereBetween('datetime', [
                      $fr->copy()->format('Y-m-d').' 06:00:00',          // '2015-11-13 06:00:00'
                      $to->copy()->addDay()->format('Y-m-d').' 05:59:59'
                    ]);
      });
  }   

  


  

    




}