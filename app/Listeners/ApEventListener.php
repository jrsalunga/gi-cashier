<?php namespace App\Listeners;

use Exception;
use Illuminate\Contracts\Mail\Mailer;

class ApEventListener
{

  private $mailer;

  public function __construct(Mailer $mailer) {
    $this->mailer = $mailer;
  }

  /**
   * Handle user Backup Success events.
   */
  public function onUpload($event) {

    $data = [
      'subject' => 'AP '.$event->branch->code.' '. $event->fileUpload->filename .' [ap.upload]',
      'to'      => app()->environment()=='production' ? $event->branch->email : 'gi.efiles@gmail.com',
      'cc'      => 'giligans.app@gmail.com',
      'btn'     => '/AP/'.$event->fileUpload->uploaddate->format('Y/m'),
      'link'    => '/'.strtolower($event->branch->code).'/ap/'.$event->fileUpload->uploaddate->format('Y/m/d').'?src=email',
      'body'    => 'AP '.$event->branch->code.' '. $event->fileUpload->filename .' has been uploaded on Cashier\'s Module'
    ];

    try {

      $this->mailer->queue('emails.ap-upload', $data, function ($message) use ($event, $data){
        $message->subject($data['subject']);
        $message->from('bot@server.loc', 'GI Alerts');
        $message->to($data['to']);
        $message->cc($data['cc']);
        $message->replyTo('gi.afd01@gmail.com', 'Giligans Accounting');
      });

    } catch (Exception $e) {

    }

    
  }

  public function subscribe($events) {
    $events->listen(
      'App\Events\Upload\Ap',
      'App\Listeners\ApEventListener@onUpload'
    );
  }
}


