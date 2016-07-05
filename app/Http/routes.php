<?php



Route::get('login', ['as'=>'auth.getlogin', 'uses'=>'Auth\AuthController@getLogin']);
Route::post('auth/login', ['as'=>'auth.postlogin', 'uses'=>'Auth\AuthController@postLogin']);
Route::get('logout', ['as'=>'auth.getlogout', 'uses'=>'Auth\AuthController@getLogout']);


Route::get('/', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);

Route::get('dashboard', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);
Route::get('timelog/daily', ['middleware' => 'auth', 'uses'=>'DashboardController@getDailyDTR']);


Route::group(['middleware' => 'auth'], function(){

Route::get('settings/{param1?}/{param2?}', ['uses'=>'SettingsController@getIndex'])
  ->where(['param1'=>'password', 
          'param2'=>'week|[0-9]+']);

Route::post('/settings/password',  ['uses'=>'SettingsController@changePassword']);


Route::get('timesheet/{param1?}', ['uses'=>'TimesheetController@getRoute']);

Route::get('backups/upload', ['uses'=>'BackupController@getUploadIndex']);
Route::get('backups/checklist', ['uses'=>'DashboardController@getChecklist']);
Route::get('backups/log', ['uses'=>'BackupController@getHistory']);
Route::get('backups/{param1?}/{param2?}', ['uses'=>'BackupController@getIndex']);
Route::post('upload/postfile', ['as'=>'upload.postfile', 'uses'=>'BackupController@postfile']); // upload to web
Route::put('upload/postfile', ['as'=>'upload.putfile', 'uses'=>'BackupController@putfile']); // move from web to storage
Route::get('download/{param1?}/{param2?}/{param3?}/{param4?}/{param5?}', ['uses'=>'BackupController@getDownload']);


Route::get('timelog/{param1?}/{param2?}', ['uses'=>'TimelogController@getIndex'])
  ->where(['param1'=>'add', 
          'param2'=>'week|[0-9]+']);
Route::post('timelog', ['uses'=>'TimelogController@manualPost']);



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


Route::get('test', ['uses'=>'BackupController@d']);



get('test/pusher', function(){

  $pusher = app('pusher');

  $pusher->trigger('test_channel', 'my_event', ['message'=>'hello world!']);

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