<?php namespace App\Http\Controllers;

use StdClass;
use Carbon\Carbon;
use App\Models\Employee;
use App\Helpers\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\DateRange;
use App\Http\Controllers\Controller;
use App\Repositories\TimelogRepository as Timelog;
use App\Events\Timelog\Timelog as TimelogEvent;
use App\Repositories\ManskeddtlRepository as MandtlRepo;


class TimesheetController extends Controller 
{ 
	public $timelog;
	public $mandtl;
	public $dr;

	public function __construct(DateRange $dr, Timelog $timelog, MandtlRepo $mandtl) {
		$this->timelog = $timelog;
		$this->dr = $dr;
		$this->mandtl = $mandtl;
	}


	public function getRoute($brcode=null, Request $request, $param1=null) {
    // return 'test';
		//event(new TimelogEvent('fasd', 'fasdfa'));
		if(!is_null($param1) && $param1=='print')
			return $this->getPrintIndex($request);
		else if(!is_null($param1) && is_uuid($param1))
			return $this->getEmployeeDtr($request, $param1);
		else
			return $this->getIndex($request);
	}


	private function getIndex(Request $request){
		
		$date = is_null($request->input('date')) 
			? $this->dr->now 
			: carbonCheckorNow($request->input('date'));
		
    return 'test carbonCheckorNow';
		$data = $this->timelog->allByDate($date);
		//return dd($data);
		return $this->setViewWithDR(view('timesheet.index')
																	->with('dr', $this->dr)
																	->with('data', $data));
	}


	private function getPrintIndex(Request $request){

		$date = is_null($request->input('date')) 
			? $this->dr->date 
			: carbonCheckorNow($request->input('date'));
		
		$data = $this->timelog->allByDate($date);

		return view('timesheet.index-print')
							->with('dr', $this->dr)
							->with('data', $data);
	}

	


	public function getParts($name = 'jefferson raga salunga') {
				
		$word = explode(' ', $name);
		$wc = str_word_count($name);

		$temp[0] = '';
		$temp[1] = '';
		$flag = false;

		for ($i=0; $i<$wc; $i++) { 
			$x = trim(strlen(implode(' ', [$temp[0], $word[$i]])));
			
			if ($x <= 18 && !$flag) {
				$temp[0] = trim(implode(' ', [$temp[0], $word[$i]]));
			} else {
				$flag = true;
				$temp[1]= trim(implode(' ', [$temp[1], $word[$i]]));
			}
		}
		return $temp;
	}


	/*
	*	chunk string attribute into array parts depends on the $limit
	*   @param: $name - attribute of the model you whant to chuck
	*   @param: $limit - length of the string you wnt to chunck
	*		@param: $line - line/index of array 
	*/

	public function getChunk($name = 'jefferson raga salunga', $limit, $line) {
				
		$word = explode(' ', $name);
		$wc = count($word);

		$temp[0] = '';
		$l=0;
		
		for ($i=0; $i<$wc; $i++) { 
					//echo $i.'-'.$word[$i].'<br>';
				
				$x = trim(strlen(implode(' ', [$temp[$l], $word[$i]])));

				if ($x <= $limit) {
					 
					$temp[$l] = trim(implode(' ', [$temp[$l], $word[$i]]));
					//echo $temp[$l].'<br>';
				} else { 
						$l++;
						
						if ($l < $line) {
							$temp[$l] = $word[$i];
						} else {
							$i = $wc;
						}
				}
		}
			
		while ($l+1 < $line) {
		  $temp[$l+1] = '';
		  $l++;  
		}

		return $temp;
	}


	private function getEmployeeDtr(Request $request, $employeeid) {

		$employee = Employee::with('position')->findOrFail($employeeid);

		$tot_tardy = 0;
		foreach ($this->dr->dateInterval() as $key => $date) {
			
			$timesheets[$key]['date'] = $date;
			
			$timelogs = $this->timelog
			->skipCriteria()
			->getRawEmployeeTimelog($employeeid, $date, $date)
			->all();

			$mandtl = $this->mandtl
									->skipCache()
									->whereHas('manskedday', function ($query) use ($date) {
										return $query->where('date', $date->format('Y-m-d'));
									})
									->findWhere(['employeeid'=>$employee->id])
									->first();

			
			$timesheets[$key]['mandtl'] = $mandtl;
			$timesheets[$key]['timelog'] = $this->timelog->generateTimesheet($employee->id, $date, collect($timelogs));

			$tardy = 0;
      if ((isset($timesheets[$key]['mandtl']->timestart) && $timesheets[$key]['mandtl']->timestart!='off') 
      && !is_null($timesheets[$key]['timelog']->timein)) {

        $timein = $timesheets[$key]['timelog']->timein->timelog->datetime;
        $timestart = c($timein->format('Y-m-d').' '.$timesheets[$key]['mandtl']->timestart);
        
        $late =$timestart->diffInMinutes($timein, false); 
        $tardy = $late>0 ? number_format(($late/60), 2) : 0;
        //$tardy = 1;

        if($tardy>0) {
          $tot_tardy+=$tardy;
        }

			}
      $timesheets[$key]['tardy'] = $tardy;
		}

		//return $timesheets;

		$header = new StdClass;
		$header->totalWorkedHours = collect($timesheets)->pluck('timelog')->sum('workedHours');
		$header->totalTardyHours = number_format($tot_tardy, 2);

		return 	$this->setViewWithDR(
							view('timesheet.employee-dtr')
							->with('timesheets', $timesheets)
							->with('employee', $employee)
							->with('header', $header)
						);
	}



	private function setViewWithDR($view){
		$response = new Response($view->with('dr', $this->dr));
		$response->withCookie(cookie('to', $this->dr->to->format('Y-m-d'), 45000));
		$response->withCookie(cookie('fr', $this->dr->fr->format('Y-m-d'), 45000));
		$response->withCookie(cookie('date', $this->dr->date->format('Y-m-d'), 45000));
		return $response;
	}



	










}

