<?php






Route::get('login', ['as'=>'auth.getlogin', 'uses'=>'Auth\AuthController@getLogin']);
Route::post('login', ['as'=>'auth.postlogin', 'uses'=>'Auth\AuthController@postLogin']);
Route::get('logout', ['as'=>'auth.getlogout', 'uses'=>'Auth\AuthController@getLogout']);


Route::get('/', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);

Route::get('dashboard', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);




Route::group(['middleware' => 'auth'], function(){


Route::get('backups/upload', ['uses'=>'BackupController@getUploadIndex']);
Route::get('backups/{param1?}/{param2?}', ['uses'=>'BackupController@getIndex']);
Route::post('upload/postfile', ['as'=>'upload.postfile', 'uses'=>'BackupController@postfile']); // upload to web
Route::put('upload/postfile', ['as'=>'upload.putfile', 'uses'=>'BackupController@putfile']); // move from web to storage



}); /******* end middeware:auth ********/






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