<?php

Route::get('login', ['as'=>'auth.getlogin', 'uses'=>'Auth\AuthController@getLogin']);
Route::post('login', ['as'=>'auth.postlogin', 'uses'=>'Auth\AuthController@postLogin']);
Route::get('logout', ['as'=>'auth.getlogout', 'uses'=>'Auth\AuthController@getLogout']);


Route::get('/', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);

Route::get('dashboard', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);




Route::group(['middleware' => 'auth'], function(){


Route::get('settings/{param1?}/{param2?}', ['uses'=>'SettingsController@getIndex'])
	->where(['param1'=>'password', 
					'param2'=>'week|[0-9]+']);

Route::post('/settings/password',  ['uses'=>'SettingsController@changePassword']);

Route::get('backups/upload', ['uses'=>'BackupController@getUploadIndex']);
Route::get('backups/history', ['uses'=>'BackupController@getHistory']);
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





get('branch', function () {
    return App\User::with(['bossbranch'=>function($query){
    	$query->select('bossid', 'branchid', 'id')
    	->with(['branch'=>function($query){
    		$query->select('code', 'descriptor', 'id');
    	}]);
    }])->get();

    


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