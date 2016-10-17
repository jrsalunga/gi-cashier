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
        
      $log = $timelogs->where('employeeid', $employeeid)
                      ->where('txncode', $i)
                      ->sortBy('datetime')
                      ->first();

      if (!is_null($log)) {
	      
	      switch ($i) {
	      	case 1:
	      		$this->timein = new Log($log);
	      		break;
	      	case 2:
	      		$this->breakin = new Log($log);
	      		break;
	      	case 3:
	      		$this->breakout = new Log($log);
	      		break;
	      	case 4:
	      		$this->timeout = new Log($log);
	      		break;
	      }
      }
    }

    $this->checkBreak();
    $this->computeWorkHours();

		return $this;
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

    	$wh->addMinutes($this->getMinDiff($this->timein->timelog->datetime, $this->timeout->timelog->datetime)); 
    } else {

    	if(!is_null($this->timein) && !is_null($this->breakin))  // meaning may laman ti at bi
        $wh->addMinutes($this->getMinDiff($this->timein->timelog->datetime, $this->breakin->timelog->datetime));
        
      // if there is a pair of breakout and timeout
      if(!is_null($this->breakout) && !is_null($this->timeout)) // meaning may laman bo at to
        $wh->addMinutes($this->getMinDiff($this->breakout->timelog->datetime, $this->timeout->timelog->datetime));
    }

    $worked = $wh->diffInMinutes($this->workHours)/60;
    if($worked<0) 
      $this->workedHours = null;
    else
      $this->workedHours = number_format($worked,2);
      
    $this->workHours = $wh;
    $this->otHours->addMinutes($this->getMinDiff($work, $this->workHours));

    $oted = $work->diffInMinutes($wh, false)/60;
    if($oted<0)
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

