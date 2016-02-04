<?php

namespace App\Providers;

use Auth;
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
