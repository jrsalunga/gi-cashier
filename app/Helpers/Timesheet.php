<?php namespace App\Helpers;

use Carbon\Carbon;
use App\Models\Timelog;
use App\Repositories\DateRange;



class Timesheet 
{

	public $timein = NULL;
	public $breakin = NULL;
	public $breakout = NULL;
	public $timeout = NULL;
	protected $timelogs;
	public $hasBreak = false;
	public $workHours;
	public $workedHours;
	public $otHours;
	public $otedHours;
	private $workingHours = 10;
  private $is_timein = false;
  private $is_breakin = false;
  private $is_breakout = false;
  private $is_timeout = false;
	

	public function __construct() {
		
	}

  public function setWorkingHours(int $hrs) {
    return $this->workingHours = is_empty($hrs) ? $this->workingHours : $hrs;
  }

	public function generate($employeeid, Carbon $date, $timelogs) {
		$this->timelogs = $timelogs;
		$this->workHours = Carbon::parse($date->format('Y-m-d').' 00:00:00');
		$this->otHours = Carbon::parse($date->format('Y-m-d').' 00:00:00');
		

		for ($i=1; $i < 5; $i++) { 
        
      if ($timelogs)  {

      $log = $timelogs->where('employeeid', $employeeid)
                      ->where('ignore', 0)
                      ->where('txncode', $i)
                      ->sortBy('datetime')
                      ->first();
      } else {
        $log = null;
      }

      if (!is_null($log)) {
        switch ($i) {
          case 1:
            $this->timein = new Log($log);
            break;
          case 2:
            if (is_null($this->breakin) && !is_null($this->timein))
              $this->breakin = $this->getLog($this->timein->timelog, $timelogs, $i, $employeeid);
            else
              $this->breakin = new Log($log);
            break;
          case 3:
            if (is_null($this->breakout) && !is_null($this->breakin))
              $this->breakout = $this->getLog($this->breakin->timelog, $timelogs, $i, $employeeid);
            else
              $this->breakout = new Log($log);
            break;
          case 4:
            if (is_null($this->timeout) && !is_null($this->breakout))
              $this->timeout = $this->getLog($this->breakout->timelog, $timelogs, $i, $employeeid);
            else
            $this->timeout = new Log($log);
            break;
        }
      }
    }

    $this->checkBreak();
    $this->computeWorkHours();

		return $this;
	}

  public function getLog(Timelog $index, $timelogs, $i, $employeeid) {

    $x = $timelogs->where('employeeid', $employeeid)
                      ->where('ignore', 0)
                      ->where('txncode', $i)
                      ->sortBy('datetime');

    foreach ($x as $key => $timelog) {
      if ($index->datetime->lt($timelog->datetime))
        return new Log($timelog);
    }
  }

	private function checkBreak() {
		if (!is_null($this->timein) && !is_null($this->breakout)
		&& !is_null($this->breakin) && !is_null($this->timeout)) 
			$this->hasBreak = true;
	}

	private function computeWorkHours(){ 


		$wh = $this->workHours->copy();
    $work = $this->workHours->copy()->addHours($this->workingHours);

    if(!is_null($this->timein) && !is_null($this->timeout) && 
    (is_null($this->breakin) || is_null($this->breakout))) {

      $this->is_timein = true;
      $this->is_breakin = false;
      $this->is_breakout = false;
      $this->is_timeout = true;
    	$wh->addMinutes($this->getMinDiff($this->timein->timelog->datetime, $this->timeout->timelog->datetime)); 
    } else {

    	if(!is_null($this->timein) && !is_null($this->breakin))  // meaning may laman ti at bi
        $wh->addMinutes($this->getMinDiff($this->timein->timelog->datetime, $this->breakin->timelog->datetime));
        $this->is_timein = true;
        $this->is_breakin = true;

      // if there is a pair of breakout and timeout
      if(!is_null($this->breakout) && !is_null($this->timeout)) // meaning may laman bo at to
        $wh->addMinutes($this->getMinDiff($this->breakout->timelog->datetime, $this->timeout->timelog->datetime));
        $this->is_breakout = true;
        $this->is_timeout = true;
    }

    $worked = $wh->diffInMinutes($this->workHours)/60;
    if($worked<=0) 
      $this->workedHours = null;
    else
      $this->workedHours = number_format($worked,2);
      
    $this->workHours = $wh;
    $this->otHours->addMinutes($this->getMinDiff($work, $this->workHours));

    $oted = $work->diffInMinutes($wh, false)/60;
    if($oted<=0)
      $this->otedHours = null;
    else
      $this->otedHours = number_format($oted,2);

    //$this->setHoursToWorkType($this->dtr->daytype, ($wh->diffInMinutes($who)/60), ($work->diffInMinutes($wh, false)/60));
  }

  // $this->computeWorkHours()
  private function getMinDiff(Carbon $time1, Carbon $time2){
    //if($time2->lt($time1)) // if timeout is less than breakout
      //$time2->addDay(); // add 1 day
    return $time2->diffInMinutes($time1);
  }

  private function getDiff(Carbon $time1, Carbon $time2){
    return $time2->diff($time1);
  }

  // $this->computeWorkHours()
  private function nt($date){ // null time ?
    $date = $date instanceof Carbon ? $date->format('H:i') : $date;
    if($date=='' || is_null($date) || $date=='00:00' || empty($date))
      return true;
    else
      return false;
  }

  public function is_timein() {
    return $this->is_timein;
  }

  public function is_breakin() {
    return $this->is_breakin;
  }

  public function is_breakout() {
    return $this->is_breakout;
  }

  public function is_timeout() {
    return $this->is_timeout;
  }




}

class Log {

public $timelog;

public function __construct(Timelog $timelog) {
	$this->timelog = $timelog;
}

public function getTimelog() {
	return $this->timelog;
}

public function __toString() {
  return $this->timelog->datetime->toDateTimeString();
  //return $this->timelog->datetime;
}




}

