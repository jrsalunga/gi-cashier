<?php namespace App\Listeners;

use App\Helpers\Locator;
use App\Model\ApUpload;
use Illuminate\Contracts\Mail\Mailer;

class ApuEventListener
{

  private $mailer;
  private $fileStorage;

  public function __construct(Mailer $mailer) {
    $this->mailer = $mailer;
    $this->fileStorage = app()->fileStorage;
  }

  public function upload($event) {

    // test_log(json_encode($e->model->filename));

    $email_k = env('AP_K_EMAIL');
    $email_c = env('AP_C_EMAIL');
    $email_csh = app()->environment('production') ? request()->user()->email : env('DEV_CSH_MAIL');
    $e = [];

    $date = $event->model->date;
    $brcode = $event->model->branch->code;
    $expl = explode('.', $event->model->filename);
    
    $e['subject'] = (is_null($expl[0] && empty($expl[0]))) ? 'No Subject' : $expl[0];
    $e['attachment'] = NULL;

    $filepath = 'APU'.DS.$date->format('Y').DS.$brcode.DS.$date->format('m').DS.$event->model->filename;
    if ($this->fileStorage->exists($filepath)) 
      $e['attachment'] = $this->fileStorage->realFullPath($filepath);

    // 1. notify the cashier
    $e['to'] = $email_csh;
    $e['body'] = $event->model->fileUpload->filename.' was sucessfully uploaded to Cashier\'s Module.';
    $this->mailer->queue('emails.notifier', $e, function ($message) use ($e) {
      $message->subject($e['subject']);
      $message->from('giligans.app@gmail.com', 'GI Payables');
      $message->to($e['to']);

      if (!is_null($e['attachment']))
        $message->attach($e['attachment']);
    });

    // 2. Accounting 
    $e['to'] = $event->model->type==2 ? env('AP_K_EMAIL') : env('AP_C_EMAIL');
    $e['replyTo'] = $email_csh;
    $e['body'] = empty(trim($event->model->remarks)) 
      ? $event->model->filename.' was sucessfully uploaded on server.'
      : trim($event->model->remarks);
    $this->mailer->queue('emails.notifier', $e, function ($message) use ($e) {
      $message->subject($e['subject']);
      $message->from('giligans.app@gmail.com', 'GI Payables');
      $message->to($e['to']);
      $message->replyTo($e['replyTo']);

      if ($e['to']!='giligans.payables@gmail.com')
        $message->cc('giligans.payables@gmail.com');
      
      if (!is_null($e['attachment']))
        $message->attach($e['attachment']);
    });
  }


  public function change($event) {

    $data = [
      'user'     => request()->user()->name,
      'action'   => 'Changed',
      'model'    => $event->old_model,
      'remarks'  => str_replace('&', '; ', str_replace('+', ' ', http_build_query($event->change)))
    ];
    
    $this->mailer->queue('docu.apu.mail-notifier', $data, function ($message) {
      $message->subject('AP - Record Update');
      $message->from('giligans.app@gmail.com', 'me'.' (giligans.app@gmail.com)');
      $message->to('giligans.app@gmail.com');
      $message->cc('giligans.payables@gmail.com');
    });
    
  }

  public function delete($event) {

    $data = [
      'user'          => request()->user()->name,
      'action'        => 'Delete',
      'filename'      => $event->model->filename,
      'doctype'      => $event->model->doctype->descriptor,
      'refno'      => $event->model->refno,
      'amount'        => number_format($event->model->amount,2),
      'supplier'       => $event->model->supplier->descriptor,
      'cashier'       => $event->model->cashier,
      'date'          => $event->model->date->format('D M j h:i:s A'),
      'remarks'       => $event->model->notes,
      'reason'        => request()->input('reason')
    ];
    
    $this->mailer->queue('docu.apu.mail-del-notifier', $data, function ($message) {
      $message->subject('AP - Delete Record');
      $message->from('giligans.app@gmail.com', 'me'.' (giligans.app@gmail.com)');
      $message->to('giligans.app@gmail.com');
      $message->cc('giligans.payables@gmail.com');
    });
    

  }

  public function subscribe($events) {
    $events->listen(
      'App\Events\Upload\ApUpload',
      'App\Listeners\ApuEventListener@upload'
    );

    $events->listen(
      'App\Events\Delete\ApUpload',
      'App\Listeners\ApuEventListener@delete'
    );

    $events->listen(
      'App\Events\Update\ApUpload',
      'App\Listeners\ApuEventListener@change'
    );
  }
}


