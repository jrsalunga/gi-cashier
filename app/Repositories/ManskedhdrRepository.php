<?php namespace App\Repositories;

use App\User;
use App\Models\Dtr;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\Manskedhdr as Mansked;
use Carbon\Carbon;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;

class ManskedhdrRepository extends BaseRepository implements CacheableInterface
{

  use CacheableRepository;

  private $manskedhdrs;

  function model() {
    return "App\\Models\\Manskedhdr";
  }

  /**
   * Get all the DTR of all employee of a branch on a certain date
   *
   * @param  User  $user
   * @return Collection
   */
  public function byBranchWithMandays(Request $request)
  {
      $this->manskedhdrs = Mansked::with('manskeddays')
                              ->where('branchid', $request->user()->branchid)
                              ->orderBy('year', 'DESC')
                              ->orderBy('weekno', 'DESC');
                              
      return $this->manskedhdrs;
  }

  private function weekNo($weekno=null){
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


  public function newWeek(Request $request){
    $arr = [];
    $obj = Mansked::where('branchid', $request->user()->branchid)->orderBy('created_at', 'DESC')->get()->first();
    if(count($obj) <= 0){
      $arr['weekno'] = date('W', strtotime('now'));
      $arr['year'] = date('Y', strtotime('now'));
      $arr['weekdays'] = $this->getDaysByWeekNo($arr['weekno']);
    } else {
      if(lastWeekOfYear() > $obj->weekno){
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


    


    
}