<?php namespace App\Listeners;

use Illuminate\Contracts\Mail\Mailer;

class NotifierEventListener
{

  private $mailer;

  public function __construct(Mailer $mailer) {
    $this->mailer = $mailer;
  }

  /**
   * Handle user Backup Success events.
   */
  public function onNotify($event) {

    $data = [
      'body'  => $event->message
    ];

    $this->mailer->queue('emails.notifier', $data, function ($message) use ($event){
      $message->subject('Notifier');
      $message->from('bot@server.loc', 'Admin');
      $message->to('giligans.app@gmail.com');
    });
  }

  public function subscribe($events) {
    $events->listen(
      'App\Events\Notifier',
      'App\Listeners\NotifierEventListener@onNotify'
    );
  }
}


