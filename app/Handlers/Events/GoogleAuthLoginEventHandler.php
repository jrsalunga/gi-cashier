<?php

namespace App\Handlers\Events;

use App\Events\GoogleUserLoggedIn;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Mail;

class GoogleAuthLoginEventHandler
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
    public function handle(GoogleUserLoggedIn $event)
    {
        //dd($event->request->user()->id);
        $data = [
            'ip' => clientIP(),
            'user' => $event->email.' via Google',
            'lat' => '',
            'lng' => '',
            'browser' => $_SERVER ['HTTP_USER_AGENT']
        ];


        Mail::queue('emails.loggedin', $data, function ($message) {
            $message->subject('User Logged In [google.login]');
            $message->from('no-reply@giligansrestaurant.com', 'GI App - Cashier');
            $message->to('giligans.app@gmail.com');
        });
    }
}
