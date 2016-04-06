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



	public function getIndex(){


		$date = carbonCheckorNow(request()->input('date'));
		$this->dr->date = $date;

		$data = $this->timelog->allByDate($date);

		return view('timesheet.index')->with('dr', $this->dr)->with('data', $data);
	}

	










}

