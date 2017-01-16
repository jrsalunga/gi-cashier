<?php namespace App\Handlers\Events\Upload;

use App\Events\Upload\Depslp;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DepslpEventHandler
{
    /**
     * Create the event handler.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Events  $event
     * @return void
     */
    public function handle(Depslp $event)
    {
        
        app('pusher')->trigger('gi.cashier', 'auth', [
          'title'=> $event->user->name.': '.$event->depslp->cashier,
          'message'=> $event->depslp->filename.' uploaded with '.number_format($event->depslp->amount, 2)
        ]);
    }
}
