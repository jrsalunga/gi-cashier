<?php

Route::get('login', ['as'=>'auth.getlogin', 'uses'=>'Auth\AuthController@getLogin']);
Route::post('auth/login', ['as'=>'auth.postlogin', 'uses'=>'Auth\AuthController@postLogin']);
Route::get('logout', ['as'=>'auth.getlogout', 'uses'=>'Auth\AuthController@getLogout']);


Route::get('/', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);
Route::get('salesmtd', ['middleware' => 'auth', 'uses'=>'SalesmtdController@test']);

Route::get('dashboard', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);
Route::get('timelog/daily', ['middleware' => 'auth', 'uses'=>'DashboardController@getDailyDTR']);

/******* start middeware:auth ********/
Route::group(['middleware' => 'auth'], function(){

Route::get('settings/{param1?}/{param2?}', ['uses'=>'SettingsController@getIndex'])
  ->where(['param1'=>'password|rfid', 
          'param2'=>'week|[0-9]+']);

Route::post('/settings/password',  ['uses'=>'SettingsController@changePassword']);
Route::post('/settings/rfid',  ['uses'=>'SettingsController@changeRfid']);

//Route::get('timesheet/{param1?}', ['uses'=>'TimesheetController@getRoute']);
Route::get('{brcode?}/timesheet/{param1?}', ['uses'=>'TimesheetController@getRoute']);

Route::get('backups/upload', ['uses'=>'BackupController@getUploadIndex']);
Route::get('backups/checklist', ['uses'=>'DashboardController@getChecklist']);
Route::get('backups/log', ['uses'=>'BackupController@getHistory']);
Route::get('backups/{param1?}/{param2?}', ['uses'=>'BackupController@getIndex']);
Route::post('upload/postfile', ['as'=>'upload.postfile', 'uses'=>'BackupController@postfile']); // upload to web
Route::put('upload/postfile', ['as'=>'upload.putfile', 'uses'=>'BackupController@putfile']); // move from web to storage
Route::get('download/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}', ['uses'=>'BackupController@getDownload']);


Route::get('{brcode?}/depslp/log', ['uses'=>'DepslpController@getHistory']);
Route::get('{brcode?}/depslp/checklist', ['uses'=>'DepslpController@getChecklist']);
Route::get('{brcode?}/depslp/{id?}/{action?}', ['uses'=>'DepslpController@getAction']);
Route::get('{brcode?}/images/depslp/{id?}', ['uses'=>'DepslpController@getImage']);
Route::put('put/depslp', ['uses'=>'DepslpController@put']);
Route::post('delete/depslp', ['uses'=>'DepslpController@delete']);

Route::get('{brcode?}/ap/log', ['uses'=>'ApController@getHistory']);
Route::get('{brcode?}/ap/checklist', ['uses'=>'ApController@getChecklist']);
Route::get('{brcode?}/ap/{id?}/{action?}/{day?}', ['uses'=>'ApController@getAction']);
Route::get('dl/ap/{p1}/{p2}/{p3}/{p4}/{p5}/{p6}', ['uses'=>'ApController@getDownload']);

Route::get('{brcode?}/setslp/log', ['uses'=>'SetslpController@getHistory']);
Route::get('{brcode?}/setslp/{id?}/{action?}', ['uses'=>'SetslpController@getAction']);
Route::get('{brcode?}/images/setslp/{id?}', ['uses'=>'SetslpController@getImage']);

Route::get('timelog/{param1?}/{param2?}', ['uses'=>'TimelogController@getIndex'])
  ->where(['param1'=>'add', 
          'param2'=>'week|[0-9]+']);
Route::post('timelog', ['uses'=>'TimelogController@manualPost']);
Route::get('{brcode}/timelog/employee/{employeeid}', ['uses'=>'TimelogController@employeeTimelog']);

Route::get('remittance/philhealth', ['uses'=>'RemittanceController@philhealthIndex']);
Route::post('remittance/upload', ['uses'=>'RemittanceController@postUpload']);
Route::get('dl/{dl}', ['uses'=>'RemittanceController@dl']);

Route::get('uploader', ['uses'=>'UploaderController@getIndex']);
Route::get('{brcode}/uploader', ['as'=>'uploader' ,'uses'=>'UploaderController@getIndex']);
Route::get('uploader/backup', ['uses'=>'UploaderController@getBackupIndex']);
Route::put('uploader/postfile', ['uses'=>'UploaderController@putFile']);
Route::get('uploader/deposit-slip', ['uses'=>'UploaderController@getDepositIndex']);
Route::get('{brcode}/upload/summary', ['as'=>'upload-summary', 'uses'=>'UploaderController@getUploadSummary']);

/******* start prefix:api     ********/
Route::group(['prefix'=>'api'], function(){  /******* begin prefix:api ********/


Route::get('search/employee', ['uses'=>'EmployeeController@search']);


}); /******* end prefix:api     ********/
}); /******* end middeware:auth ********/

// for TK
Route::post('api/timelog', ['as'=>'timelog.post', 'uses'=>'TimelogController@post']);
Route::get('tk', ['as'=>'tk.index','uses'=>'TimelogController@getTkIndex']);
Route::get('api/employee/{field?}/{value?}', ['as'=>'employee.getbyfield', 'uses'=>'EmployeeController@getByField']);



Route::get('auth/google', 'Auth\AuthController@redirectToProvider');
Route::get('oauth2callback', 'Auth\AuthController@handleProviderCallback');


get('test', function(){

  return Carbon\Carbon::parse('- -');
  $c = Carbon\Carbon::parse('2016-05-26 06:00:00');
  $t = Carbon\Carbon::parse('2016-05-26 04:00:00');




  //return 
  return $c->lte($t)
    ? 'same '. $t->format('Y-m-d')
    : '- day '.$t->copy()->subDay()->format('Y-m-d');

});


get('test/pusher', function(){

  $pusher = app('pusher');

  $pusher->trigger('gi.cashier', 'auth', [
    'module'=>'Giligan\'s Cashier Module', 
    'message'=>'hello world!'
  ]);

  return;

});


get('test/component', function(){
  return App\Models\Component::with('compcat.expense.expscat')->find('06156841637011E5B83800FF59FBB323');
});

get('test/compledger', function(){
  return App\Models\Compledger::with('branch')->get();
});

Route::get('pepsi', ['uses'=>'DashboardController@pepsi']);



get('date/compare', function(){
  $backupdate = Carbon\Carbon::parse('2016-05-25');
  $last_purchase = Carbon\Carbon::parse('2016-05-24');

  echo $last_purchase->format('Y-m-d').' < '.$backupdate->format('Y-m-d').'</br>';
  if($last_purchase->lte($backupdate))
    echo 'process all transaction ';
  else 
    echo 'process only transaction dated as backup date';
});


get('date/diff', function(){
  $timein = Carbon\Carbon::parse('2016-10-04 10:11:57');
  $timeout = Carbon\Carbon::parse('2016-10-04 22:11:05');
  $workHrs = Carbon\Carbon::parse($timein->format('Y-m-d').' 00:00:00');

  $workHrs->addMinutes($timeout->diffInMinutes($timein));
  return $workHrs->format('Y-m-d H:i:s');

  return $timeout->diff($timein)->format('%H:%i:%s');
});


get('test/array/set', function(){
  $date = Carbon\Carbon::parse('2016-05-25');


  $data = [];
  $data['date'] = $date;
  $data['branch'] = 'MOA';
  $data['sales']  = 1000.00;

  array_set($data, 'date', $date->format('Y-m-d'));

  return $data;
});


get('test/array/only', function(){
  $date = Carbon\Carbon::parse('2016-05-25');


  $data = [];
  $data['date'] = $date;
  $data['branch'] = 'MOA';
  $data['sales']  = 1000.00;

  return array_only($data, ['date', 'branch']);

  
});



get('sessions', function(){
	return session()->all();
});

get('/env', function() {
  return app()->environment();
});

get('/env/hostname', function() {
  return gethostname();
});

get('/env/clientname', function(){
	return gethostbyaddr($_SERVER['REMOTE_ADDR']);
});

get('/phpinfoko', function(){
	return phpinfo();
});

get('/env/vars', function(){
  echo 'MANDRILL_APIKEY - '.getenv('MANDRILL_APIKEY');
});

get('/checkdbconn', function(){
	if(DB::connection()->getDatabaseName()){
	  echo "connected sucessfully to database ".DB::connection()->getDatabaseName();
	}
});

get('/v', function(){
  dd($app->version());
});