<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Handlers\Events\ChangePasswordEventHandler;
use App\Handlers\Events\AuthLoginEventHandler;
use App\Handlers\Events\GoogleAuthLoginEventHandler;
use App\Handlers\Events\AuthLoginErrorEventHandler;
use App\Handlers\Events\GoogleAuthLoginErrorEventHandler;
use App\Events\UserChangePassword;
use App\Events\UserLoggedIn;
use App\Events\GoogleUserLoggedIn;
use App\Events\UserLoggedFailed;
use App\Events\GoogleUserLoggedFailed;
use App\Listeners\AuthLoginEventListener;
use App\Listeners\BackupEventListener;
use App\Listeners\DepslpEventListener;
use App\Listeners\SetslpEventListener;
use App\Events\Upload\Depslp;
use App\Handlers\Events\Upload\DepslpEventHandler;
use App\Listeners\NotifierEventListener;
use App\Listeners\ApEventListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\SomeEvent' => [
            'App\Listeners\EventListener',
        ],
        UserLoggedIn::class => [
            AuthLoginEventHandler::class,
            //AuthLoginEventListener::class, //separate event
        ],
        UserChangePassword::class => [
            ChangePasswordEventHandler::class,
        ],
        UserLoggedFailed::class => [
            AuthLoginErrorEventHandler::class,
        ],
        GoogleUserLoggedIn::class => [
            GoogleAuthLoginEventHandler::class,
        ],
        GoogleUserLoggedFailed::class => [
            GoogleAuthLoginErrorEventHandler::class,
        ]
    ];
    
    protected $subscribe = [
        //AuthLoginEventListener::class,
        BackupEventListener::class,
        DepslpEventListener::class,
        NotifierEventListener::class,
        ApEventListener::class,
        SetslpEventListener::class,
    ];
    
    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
