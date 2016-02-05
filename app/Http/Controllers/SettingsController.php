<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Validator;
use Auth;
use App\Events\UserChangePassword;

class SettingsController extends Controller {



	public function getIndex(Request $request, $param1=null, $param2=null){
		if(strtolower($param1)==='add')
			return $this->makeAddView($request);
		else if(preg_match('/(20[0-9][0-9])/', $param1) && (strtolower($param2)==='week') && preg_match('/^[0-9]+$/', $param3)) //((strtolower($param1)==='week') && preg_match('/^[0-9]+$/', $param2)) 
			return $this->makeViewWeek($request, $param1, $param3); //task/mansked/2016/week/7
		else if(preg_match('/^[A-Fa-f0-9]{32}+$/', $param1) && strtolower($param2)==='edit')
			return $this->makeEditView($request, $param1);
		else if($param1==='password' && $param2==null)   //preg_match('/^[A-Fa-f0-9]{32}+$/',$action))
			return $this->makePasswordView($request, $param1, $param2);
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



	
}