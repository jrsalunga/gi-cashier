<?php namespace App\Repositories;

use DateInterval;
use DatePeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class DateRange {

  public $fr;
  public $to;
  public $date;
  public $now;


  public function __construct(Request $request, $now = null) {
  	$this->date = $this->carbonCheckorNow($request->input('date'));
  	$this->setDates($request);
  	$this->now = Carbon::now();
  }

  private function checkDates(Request $request) {
  	
  	try {
			$this->fr = Carbon::parse($request->input('fr').' 00:00:00');
		} catch(\Exception $e) {
			//$this->fr = Carbon::parse($this->date->year.'-'.$this->date->month.'-01 00:00:00');
			$this->fr = $this->date;
		}
		
		try {
			$this->to = Carbon::parse($request->input('to').' 00:00:00');
		} catch(\Exception $e) {
			//$this->to = Carbon::parse($this->date->year.'-'.$this->date->month.'-'.$this->date->daysInMonth.' 00:00:00');
			$this->to = $this->date;
		}
		
		// if to less than fr
		if($this->to->lt($this->fr))
			$this->to = Carbon::parse($this->fr->year.'-'.$this->fr->month.'-'.$this->fr->daysInMonth.' 00:00:00');
  	
  }

  public function setDates(Request $request) {

  	if (is_null($request->input('fr')) && !is_null($request->input('to'))) {
			
			$this->fr = Carbon::parse(date('Y-m-01', strtotime($request->input('to'))).' 00:00:00');	
			$this->to = Carbon::parse(date('Y-m-d', strtotime($request->input('to'))).' 00:00:00');
		
		} else if (!is_null($request->input('fr')) && is_null($request->input('to'))){

			$this->fr = Carbon::parse(date('Y-m-d', strtotime($request->input('fr'))).' 00:00:00');	
			$this->to = Carbon::parse(date('Y-m-t', strtotime($request->input('fr'))).' 00:00:00');

		} else if(!is_null($request->input('date'))) {

			$this->date = $this->carbonCheckorNow($request->input('date'));	
			$this->fr = $this->date;	
			$this->to = $this->date;

		} else if(is_null($request->input('fr')) && is_null($request->input('to')) && is_null($request->input('date'))){

			$this->getCurrentNewOrCookie($request);
		
		} else {

			$this->checkDates($request);

		}
  }



  private function getCurrentNewOrCookie(Request $request){

		if (is_null($request->cookie('fr')))
      $this->fr = Carbon::parse($this->date->year.'-'.$this->date->month.'-01 00:00:00');
		else 
			$this->fr = Carbon::parse($request->cookie('fr'));
		
		if (is_null($request->cookie('to')))
      $this->to =  Carbon::parse($this->date->year.'-'.$this->date->month.'-'.$this->date->daysInMonth.' 00:00:00');
		else 
			$this->to = Carbon::parse($request->cookie('to'));

		if (is_null($request->cookie('date')))
      $this->date =  Carbon::parse($this->date->year.'-'.$this->date->month.'-'.$this->date->day.' 00:00:00');
		else 
			$this->date = Carbon::parse($request->cookie('date'));
  }


  public function dateInterval_old(){
  	$to = $this->to->copy();
    $interval = new DateInterval('P1D');
    $to->add($interval);
    return new DatePeriod($this->fr, $interval, $to);
  }

  public function dateInterval(){
    $fr = $this->fr->copy();
    $arr = [];
    do {
      array_push($arr, Carbon::parse($fr->format('Y-m-d').' 00:00:00'));
    } while ($fr->addDay() <= $this->to);
    return $arr;
  }

  public function monthInterval(){
  	$fr = $this->fr->copy();
  	$arr = [];
  	 do {
      array_push($arr, Carbon::parse($fr->format('Y-m-d')));
    } while ($fr->addMonth() <= $this->to);

    return $arr;
  }

  public function carbonCheckorNow($date=NULL) {

		if(is_null($date))
			return Carbon::now();
		
		try {
			return Carbon::parse($date); 
		} catch(Exception $e) {
			return Carbon::now(); 
		}
	}


}