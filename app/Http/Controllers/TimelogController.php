<?php namespace App\Http\Controllers;

use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Timelog;
use App\Models\Employee;
use App\Repositories\TimelogRepository;
use Carbon\Carbon;

class TimelogController extends Controller {

	private $repository;
	private $_branchid;

	public function __construct(TimelogRepository $repo, Request $request){
		$this->repository = $repo;

		if(is_null(session('user.branchid')) && is_null($request->cookie('branchid')))
			return redirect()->route('auth.getlogin');
		else
			$this->_branchid = is_null(session('user.branchid')) ? $request->cookie('branchid') : session('user.branchid');
	
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

	public function manualPost(Request $request) {
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


  public function post(Request $request){
		

		$rules = array(
			//'employeeid'	=> 'required',
			'datetime'      => 'required',
			'txncode'      	=> 'required',
			'entrytype'     => 'required',
			//'terminalid'    => 'required',
		);
		
		$validator = Validator::make($request->all(), $rules);


		if($validator->fails()) {
			
			$respone = array(
					'code'=>'400',
					'status'=>'error',
					'message'=>'Error on validation',
					//'data'=> $validator
			);
		} else {
			$employee = Employee::with('branch', 'position')->where('rfid', '=', $request->input('rfid'))->first();
			
			
			if(!isset($employee)){ // employee does not exist having the RFID submitted
				$respone = array(
						'code'=>'401',
						'status'=>'error',
						'message'=>'Invalid RFID: '.  $request->input('rfid'),
						'data'=> ''
				);	
			} else {
			
				$timelog = new Timelog;
				//$timelog->employeeid	= $request->get('employeeid');
				$timelog->employeeid  = $employee->id;
				$timelog->datetime 		= $request->input('datetime');
				$timelog->txncode 	 	= (strtolower($employee->branchid) == strtolower($this->_branchid)) ? $request->input('txncode'):'9';
				$timelog->entrytype  	= $request->input('entrytype');
				$timelog->rfid				= $employee->rfid;
				$timelog->terminalid 	= $request->cookie('branchcode')!==null ? $request->cookie('branchcode'):$_SERVER["REMOTE_ADDR"];
				//$timelog->terminal 	= gethostname();
				$timelog->id 	 	 			= strtoupper(Timelog::get_uid());
				
				if($timelog->save()){

					$respone = array(
						'code'=>'200',
						'status'=>'success',
						'message'=>'Record saved!',
					);	

					$datetime = explode(' ',$timelog->datetime);
				
					$data = array(
						'empno'			=> $employee->code,
						'lastname'	=> $employee->lastname,
						'firstname'	=> $employee->firstname,
						'middlename'=> $employee->middlename,
						
						'position'	=> $employee->position->descriptor,
						'date'			=> $datetime[0] ,
						'time'			=> $datetime[1] ,
						'txncode'		=> $timelog->txncode,
						'txnname'		=> $timelog->getTxnCode(),
						'branch' 		=> $employee->branch->code,
						'timelogid' => $timelog->id,
					);
				
					$respone['data'] = $data;

				} else {
					$respone = array(
						'code'=>'400',
						'status'=>'error',
						'message'=>'Error on saving locally!',
					);	
				}				
			}
		}
		return json_encode($respone);
	}

  public function getTkIndex(Request $request) {
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']==='127.0.0.1'){
			
			$timelogs = Timelog::with('employee.branch')
											->orderBy('datetime', 'DESC')
											->take(20)
											->get();
		} else {

			$timelogs = Timelog::with(['employee'=>function($query){
													
													$query->with([
															'branch'=>function($query){
																$query->select('code', 'descriptor', 'id');
															}, 
															'position'=>function($query){
																$query->select('code', 'descriptor', 'id');
															}])->select('code', 'lastname', 'firstname', 'branchid', 'positionid', 'id');
														
												}])
											->select('timelog.*')
											->join('hr.employee', function($join) use ($request) {
                            $join->on('timelog.employeeid', '=', 'employee.id')
                                ->where('employee.branchid', '=', $this->_branchid);
                            })
											->orderBy('datetime', 'DESC')
											->take(20)
											->get();

			
		}

		//return $timelogs;
		//if(count($timelogs) <= 0)
		//	return redirect()->route('auth.getlogin');
		
		$response = new Response(view('tk.index', compact('timelogs')));//->with('timelogs', $timelogs));
		$response->withCookie(cookie('branchid', $this->_branchid, 45000));
		$response->withCookie(cookie('code', session('user.branchcode'), 45000));
		return $response;

    //return view('tk.index', compact($timelogs));//->with('timelogs', $timelogs);		
	}


	



	
}