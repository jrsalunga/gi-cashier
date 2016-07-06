<?php

namespace App\Handlers\Events;

use App\Events\GoogleUserLoggedFailed;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Mail;

class GoogleAuthLoginErrorEventHandler
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
    public function handle(GoogleUserLoggedFailed $event)
    {
        //dd($event->request->user()->id);
        $data = [
            'ip' => clientIP(),
            'user' => $event->request->email.' via Google',
            'password' => '',
            'browser' => $_SERVER ['HTTP_USER_AGENT']
        ];


        Mail::queue('emails.loggederror', $data, function ($message) {
            $message->subject('Failed Logged In');
            $message->from('no-reply@giligansrestaurant.com', 'GI App - Cashier');
            $message->to('giligans.app@gmail.com');
        });
    }
}
