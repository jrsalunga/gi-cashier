<?php namespace App\Http\Controllers;

use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timelog;
use App\Models\Employee;
use App\Repositories\TimelogRepository;
use Carbon\Carbon;

class TimelogController extends Controller {

	private $repository;

	public function __construct(TimelogRepository $repo){
		$this->repository = $repo;
	}



	public function getIndex(Request $request, $param1=null, $param2=null){
		if(strtolower($param1)==='add' && is_null($param2))
			return $this->makeAddView($request, $param1, $param2);
		else
			return $this->makeIndexView($request, $param1, $param2);
	}



	public function makeIndexView(Request $request, $p1, $p2) {

		
		//return dd($this->repository->all());
		$timelogs = $this->repository->with(['employee'])->paginate(10, $columns = ['*']);
		//return dd($timelogs);
		return view('timelog.index')->with('timelogs', $timelogs);	
	}

	public function makeAddView(Request $request, $p1, $p2) {

		
		
		return view('timelog.add');	
	}

	public function post(Request $request) {
  	$rules = array(
			'employeeid'	=> 'required|max:32|min:32',
			'date'      	=> 'required|date',
			'time' 				=> 'required',
		);

		$messages = [
	    'employeeid.required' => 'Employee is required.',
	    'employeeid.max' => 'Employee is required..',
	    'employeeid.min' => 'Employee is required...',
	    'time.date_format' => 'The time does not match the format HH:MM. (e.g 02:30, 17:00)',
		];
		
		$validator = Validator::make($request->all(), $rules, $messages);

		if ($validator->fails())
			return redirect('/timelog/add')->withErrors($validator);
		
		try {
			$datetime = Carbon::parse($request->input('date').' '.$request->input('time'));
		} catch (\Exception $e) {
			return redirect('/timelog/add')->withErrors(
				['message'=>'The time does not match the format 12 Hrs (01:30 PM) or 24 Hrs (13:30)']);
		}

		$employee = Employee::where('id', $request->input('employeeid'))
												->where('branchid', $request->user()->branchid)
												->first();

		if(is_null($employee)) {
			return redirect('/timelog/add')->withErrors(
				['message' => 'Employee not found on this branch.']);
		}



		$attributes = [
			'employeeid' 	=> $employee->id,
			'datetime' 		=> $datetime->format('Y-m-d H:i').':00',
			'txncode' 		=> $request->input('txncode'),
			'entrytype' 	=> 2,
			'terminalid'	=> clientIP()
		];

		$timelog = $this->repository->create($attributes);
				
		if (is_null($timelog)) {
			return redirect('/timelog/add')->withErrors(
				['message' => 'Unable to save timelog.']);
		} else {
			return redirect('/timelog/add')
				->with('alert-success', 'Timelog saved!');
		}




		return $timelog;
  }


	



	
}