<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Validator;
use Auth;
use App\Events\UserChangePassword;
use App\Repositories\EmployeeRepository as EmployeeRepo;

class SettingsController extends Controller {

	protected $employee;

	public function __construct(EmployeeRepo $employee) {
		$this->employee = $employee;
	}


	public function getIndex(Request $request, $param1=null, $param2=null){
		if(strtolower($param1)==='add')
			return $this->makeAddView($request);
		else if(preg_match('/(20[0-9][0-9])/', $param1) && (strtolower($param2)==='week') && preg_match('/^[0-9]+$/', $param3)) //((strtolower($param1)==='week') && preg_match('/^[0-9]+$/', $param2)) 
			return $this->makeViewWeek($request, $param1, $param3); //task/mansked/2016/week/7
		else if(preg_match('/^[A-Fa-f0-9]{32}+$/', $param1) && strtolower($param2)==='edit')
			return $this->makeEditView($request, $param1);
		else if($param1==='password' && $param2==null)   //preg_match('/^[A-Fa-f0-9]{32}+$/',$action))
			return $this->makePasswordView($request, $param1, $param2);
		else if($param1==='rfid' && $param2==null)   //preg_match('/^[A-Fa-f0-9]{32}+$/',$action))
			return $this->makeRfidView($request);
		else
			return $this->makeIndexView($request, $param1, $param2);
	}



	public function makeIndexView(Request $request, $p1, $p2) {

		$user = User::with('branch')
					->where('id', $request->user()->id)
					->first();
	
		return view('settings.index')->with('user', $user);	
	}

	public function makePasswordView(Request $request, $p1, $p2) {

	
		return view('settings.password');	
	}

	public function changePassword(Request $request) {

		$rules = array(
			'passwordo'      => 'required|max:50',
			'password'      	=> 'required|confirmed|max:50|min:8',
			'password_confirmation' => 'required|max:50|min:8',
		);

		$messages = [
	    'passwordo.required' => 'Old password is required.',
	    'password.required' => 'New password is required.',
		];
		
		$validator = Validator::make($request->all(), $rules, $messages);

		if ($validator->fails())
			return redirect('/settings/password')->withErrors($validator);

		if (!Auth::attempt(['username'=>$request->user()->username, 'password'=>$request->input('passwordo')]))
			return redirect('/settings/password')->withErrors(['message'=>'Invalid old password.']);

		$user = User::find($request->user()->id)
								->update(['password' => bcrypt($request->input('password'))]);
		
		if(!$user)
			return redirect('/settings/password')->withErrors(['message'=>'Unable to change password.']);
		
		event(new UserChangePassword($request));

		return redirect('/settings/password')->with('alert-success', 'Password change!');
		return view('settings.password');	
	}


	private function makeRfidView(Request $request) {

		return view('settings.rfid');
	}

	public function changeRfid(Request $request) {

		$rules = array(
			'employeeid'      => 'required|max:32',
			'rfid'      	=> 'required|numeric',
		);

		$messages = [
	    'employeeid.required' => 'Employee field is required.',
	    'rfid.required' => 'RFID is required.',
	    'rfid.numeric' => 'Invalid RFID.',
		];

		$validator = Validator::make($request->all(), $rules, $messages);

		if ($validator->fails())
			return redirect('/settings/rfid')->withErrors($validator);

		$rfid = $this->employee->findByField('rfid', $request->input('rfid'), ['code', 'firstname', 'lastname', 'rfid', 'empstatus', 'id'])->first();
		if ($rfid->empstatus > 0)
			return redirect('/settings/rfid')->withErrors('RFID already assigned to '. $rfid->lastname.', '.$rfid->firstname);

		$employee = $this->employee->find($request->input('employeeid'), ['code', 'firstname', 'lastname', 'rfid', 'id']);
		if(is_null($employee))
			return redirect('/settings/rfid')->withErrors('Employee not found!');

		if($this->employee->update(['rfid'=>$rfid->rfid], $employee->id))
			return redirect('settings/rfid')->withSuccess('RFID updated!');
		else
			return redirect('settings/rfid')->withErrors('Something went wrong on saving RFID.');
	}



	
}