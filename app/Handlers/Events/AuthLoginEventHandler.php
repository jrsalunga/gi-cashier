<?php

namespace App\Handlers\Events;

use App\Events\UserLoggedIn;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Mail;

class AuthLoginEventHandler
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
    public function handle(UserLoggedIn $event)
    {
        $data = [
            'ip' => clientIP(),
            'user' => $event->request['username'],
            'lat' => $event->request['lat'],
            'lng' => $event->request['lng'],
            'browser' => $_SERVER ['HTTP_USER_AGENT']
        ];
        /*
        app('pusher')->trigger('gi.cashier', 'auth', [
          'title'=>'Giligan\'s Cashier', 
          'message'=> $data['user'].' successfully logged in at this IP: '.clientIP()
        ]);
        */
        Mail::queue('emails.loggedin', $data, function ($message) {
            $message->subject('User Logged In');
            $message->from('no-reply@giligansrestaurant.com', 'GI App - Cashier');
            $message->to('giligans.app@gmail.com');
        });
    }
}
