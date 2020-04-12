<?php

namespace App\Providers;
use DB;
use Auth;
use Validator;
use App\User;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('*', function($view){
            
            $id = empty(Auth::user()->id) ? '':Auth::user()->id;

            if(strtolower($id)!==strtolower(session('user.id'))){
                $emp = User::with(['branch'=>function($query){
                                $query->select('code', 'descriptor', 'mancost', 'id');
                            }])->where('id', Auth::user()->id)
                            ->get(['name', 'branchid', 'id'])->first();
                session(['user' => ['fullname'=>$emp->name, 
                        'id'=>$emp->id, 'branchid'=>$emp->branchid, 
                        'branch'=>$emp->branch->descriptor, 
                        'branchcode'=>$emp->branch->code,
                        'branchmancost'=>$emp->branch->mancost]]);
            }
            
            
            $view->with('name', session('user.fullname'))->with('branch',  session('user.branch'));
        });

        /*
        DB::listen(function($sql, $bindings, $time) {
            test_log($sql);
            test_log(join(',', $bindings));
        });
        */

        Validator::extend('alpha_spaces', function ($attribute, $value) {
            // This will only accept alpha and spaces. 
            // If you want to accept hyphens use: /^[\pL\s-]+$/u.
            //return preg_match('/^[\pL\s]+$/u', $value); 
            return preg_match('/^[\pL\s-]+$/u', $value); 
        });

        // alpha numeric space hypen underscore period comma
        Validator::extend('anshupc', function ($attribute, $value) {
            return preg_match('/^[0-9\pL\s-_.,]+$/u', $value); 
        });

        // export request file
        Validator::extend('exportreq', function ($attribute, $value) {
            return preg_match('/EX(?!0{6})\d{6}\.REQ/u', $value); 
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
