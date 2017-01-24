<?php namespace App\Listeners;

use Illuminate\Contracts\Mail\Mailer;

class DepslpEventListener
{

  private $mailer;

  public function __construct(Mailer $mailer) {
    $this->mailer = $mailer;
  }

  


  public function updated($depslp) {

    $data = [
      'user'     => request()->user()->name,
      'action'   => 'Updated',
      'depslp'   => $depslp,
      'remarks'  => ''
    ];

    $this->mailer->queue('docu.depslp.mail-notifier', $data, function ($message) {
      $message->subject('Depslp - Updated');
      $message->from('giligans.app@gmail.com', 'me'.' (giligans.app@gmail.com)');
      $message->to('giligans.app@gmail.com');
    });
  }


  public function changed($payload) {

    $data = [
      'user'     => request()->user()->name,
      'action'   => 'Changed',
      'depslp'   => $payload,
      'remarks'  => ''
    ];
    
    $this->mailer->queue('docu.depslp.mail-notifier', $data, function ($message) {
      $message->subject('Depslp - Changed');
      $message->from('giligans.app@gmail.com', 'me'.' (giligans.app@gmail.com)');
      $message->to('giligans.app@gmail.com');
    });
    
  }


  public function change($event) {

    $data = [
      'user'     => request()->user()->name,
      'action'   => 'Changed',
      'depslp'   => $event->new_depslp,
      'remarks'  => http_build_query($event->change)
    ];
    
    $this->mailer->queue('docu.depslp.mail-notifier', $data, function ($message) {
      $message->subject('Depslp - Changed');
      $message->from('giligans.app@gmail.com', 'me'.' (giligans.app@gmail.com)');
      $message->to('giligans.app@gmail.com');
    });
    
  }

  public function deleted($event) {

    $data = [
      'user'          => request()->user()->name,
      'action'        => 'Delete',
      'filename'      => $event->depslp['filename'],
      'amount'        => number_format($event->depslp['amount'],2),
      'cashier'       => $event->depslp['cashier'],
      'deposit_date'  => $event->depslp['deposit_date']->format('D M j h:i:s A'),
      'remarks'       => $event->depslp['remarks']
    ];
    
    $this->mailer->queue('docu.depslp.mail-del-notifier', $data, function ($message) {
      $message->subject('Depslp - Delete');
      $message->from('giligans.app@gmail.com', 'me'.' (giligans.app@gmail.com)');
      $message->to('giligans.app@gmail.com');
    });
    

  }

  public function subscribe($events) {
    $events->listen(
      'depslp.updated',
      'App\Listeners\DepslpEventListener@updated'
    );
    /*
    $events->listen(
      'depslp.deleted',
      'App\Listeners\DepslpEventListener@deleted'
    );
    */
    $events->listen(
      'depslp.changed',
      'App\Listeners\DepslpEventListener@changed'
    );

    $events->listen(
      'App\Events\Depslp\Change',
      'App\Listeners\DepslpEventListener@change'
    );

    $events->listen(
      'App\Events\Depslp\Delete',
      'App\Listeners\DepslpEventListener@deleted'
    );
  }
}


