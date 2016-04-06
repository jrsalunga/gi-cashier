<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\DateRange;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Container\Container as App;
use App\Repositories\TimelogRepository as Timelog;

class TimesheetController extends Controller 
{ 
	public $timelog;

	public function __construct(DateRange $dr, Timelog $timelog) {
		$this->timelog = $timelog;
		$this->dr = $dr;
	}


	public function getRoute(Request $request, $param1=null) {
		if(!is_null($param1) && $param1=='print')
			return $this->getPrintIndex($request);
		else
			return $this->getIndex($request);
	}


	private function getIndex(Request $request){
		
		$date = is_null($request->input('date')) 
			? $this->dr->date 
			: carbonCheckorNow($request->input('date'));
		
		$data = $this->timelog->allByDate($date);
		
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

	private function setViewWithDR($view){
		$response = new Response($view->with('dr', $this->dr));
		$response->withCookie(cookie('to', $this->dr->to->format('Y-m-d'), 45000));
		$response->withCookie(cookie('fr', $this->dr->fr->format('Y-m-d'), 45000));
		$response->withCookie(cookie('date', $this->dr->date->format('Y-m-d'), 45000));
		return $response;
	}

	










}

