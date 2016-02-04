<?php






Route::get('login', ['as'=>'auth.getlogin', 'uses'=>'Auth\AuthController@getLogin']);
Route::post('login', ['as'=>'auth.postlogin', 'uses'=>'Auth\AuthController@postLogin']);
Route::get('logout', ['as'=>'auth.getlogout', 'uses'=>'Auth\AuthController@getLogout']);


Route::get('/', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);

Route::get('dashboard', ['middleware' => 'auth', 'uses'=>'DashboardController@getIndex']);






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