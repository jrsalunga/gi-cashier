<?php namespace App\Listeners;

use Illuminate\Contracts\Mail\Mailer;

class BackupEventListener
{

  private $mailer;

  public function __construct(Mailer $mailer) {
    $this->mailer = $mailer;
  }

  /**
   * Handle user Backup Success events.
   */
  public function onProcessSuccess($event) {

    $data = [
      'user'      => $event->user->name,
      'cashier'   => $event->backup->cashier,
      'filename'  => $event->backup->filename,
    ];

    $this->mailer->queue('emails.backup-processsuccess', $data, function ($message) use ($event){
      $message->subject('Backup Upload');
      $message->from($event->user->email, $event->user->name.' ('.$event->user->email.')');
      $message->to('giligans.app@gmail.com');
    });
  }

  public function subscribe($events) {
    $events->listen(
      'App\Events\Backup\ProcessSuccess',
      'App\Listeners\BackupEventListener@onProcessSuccess'
    );
  }
}

