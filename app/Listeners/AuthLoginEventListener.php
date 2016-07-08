<?php namespace App\Listeners;

use App\Jobs\Job;
use App\Events\UserLoggedIn;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Handlers\Events\AuthLoginEventHandler;

// to debug .env queue=database & mysql=job table
class AuthLoginEventListener extends Job implements ShouldQueue
{

  /**
   * Create the event listener.
   *
   * @return void
   */
  public function __construct() { 

  }

  /**
   * Handle the event.
   *
   * @param  ActionDone  $event
   * @return void
   */
  public function handle(UserLoggedIn $event)
  {
    app('pusher')->trigger('gi.cashier', 'auth', [
      'title'=>'Giligan\'s Cashier', 
      'message'=> $event->request['username'].' successfully logged in at this IP: '
    ]);
  }

  public function subscribe($events)
  {
    $events->listen(
        UserLoggedIn::class,
        AuthLoginEventHandler::class
    );
  }
}


