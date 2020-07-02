<?php namespace App\Listeners;

use App\Helpers\Locator;
use App\Model\ApUpload;
use App\Helpers\BossBranch;
use Illuminate\Contracts\Mail\Mailer;

class ApuEventListener
{

  private $mailer;
  private $bossBranch;
  private $fileStorage;

  public function __construct(Mailer $mailer, BossBranch $bossBranch) {
    $this->mailer = $mailer;
    $this->bossBranch = $bossBranch;
    $this->fileStorage = app()->fileStorage;
  }

  public function upload($event) {

    // test_log(json_encode($e->model->filename));
    $brcode = $event->model->branch->code;

    $email_k = env('AP_K_EMAIL');
    $email_c = env('AP_C_EMAIL');
    $email_csh = app()->environment('production') ? request()->user()->email : env('DEV_CSH_MAIL');
    $e = [];
    if (app()->environment('production')) {
      $l = 'http://am.giligansrestaurant.com/ap/';
      $c = 'http://cashier.giligansrestaurant.com/'.strtolower($brcode).'/apu/';
      // $e['mailing_list'] = $this->bossBranch->getUsers()->toArray();
      $e['mailing_list'] = [
        ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
        ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
      ];
    } else {
      $l = 'http://gi-am.loc/ap/';
      $c = 'http://gi-cashier.loc/'.strtolower($brcode).'/apu/';
      $e['mailing_list'] = [
        ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
        ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
      ];
    }

    $date = $event->model->date;
    $expl = explode('.', $event->model->filename);
    
    $e['subject'] = (is_null($expl[0] && empty($expl[0]))) ? 'No Subject' : $expl[0];
    $e['model'] = $event->model;
    $e['attachment'] = NULL;

    $filepath = 'APU'.DS.$date->format('Y').DS.$brcode.DS.$date->format('m').DS.$event->model->filename;
    if ($this->fileStorage->exists($filepath)) 
      $e['attachment'] = $this->fileStorage->realFullPath($filepath);

    // 1. notify the cashier
    $e['to'] = $email_csh;
    $e['link'] =  $c.$event->model->lid();
    $this->mailer->queue('docu.apu.mail-upload', $e, function ($message) use ($e) {
      $message->subject($e['subject']);
      $message->from('giligans.app@gmail.com', 'GI Payables');
      $message->to($e['to']);

      // if (!is_null($e['attachment']))
      //   $message->attach($e['attachment']);
    });

    // 2. RM, AHC 
    $e['to'] = $event->model->type==2 ? env('AP_K_EMAIL') : env('AP_C_EMAIL');
    $e['replyTo'] = $email_csh;
    $e['link'] =  $l.$event->model->lid();
    $this->mailer->queue('docu.apu.mail-verify', $e, function ($message) use ($e) {
      $message->subject($e['subject']);
      $message->from('giligans.app@gmail.com', 'GI Payables');
      $message->replyTo($e['replyTo']);

       foreach ($e['mailing_list'] as $u)
        $message->to($u['email'], $u['name']);
        
      $message->cc('giligans.app@gmail.com');

      // if (!is_null($e['attachment']))
        // $message->attach($e['attachment']);
    });

    // 3. Accounting
    $e['to'] = $event->model->type==2 ? env('AP_K_EMAIL') : env('AP_C_EMAIL');
    $e['replyTo'] = $email_csh;
    $e['link'] =  NULL;
    $this->mailer->queue('docu.apu.mail-upload', $e, function ($message) use ($e) {
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
      $message->subject('AP - Update Record');
      $message->from('giligans.app@gmail.com', 'GI Alerts');
      $message->to('giligans.app@gmail.com');
      $message->cc('giligans.payables@gmail.com');
      $message->cc('jefferson.salunga@gmail.com');
    });
    
  }

  public function delete($event) {
    $data = [
      'user'          => request()->user()->name,
      'action'        => 'Delete',
      'filename'      => $event->model->filename,
      'doctype'       => $event->model->doctype->descriptor,
      'refno'         => $event->model->refno,
      'amount'        => number_format($event->model->amount,2),
      'supplier'      => $event->model->supplier->descriptor,
      'cashier'       => $event->model->cashier,
      'date'          => $event->model->date->format('D M j h:i:s A'),
      'remarks'       => $event->model->notes,
      'reason'        => request()->input('reason')
    ];
    
    $this->mailer->queue('docu.apu.mail-del-notifier', $data, function ($message) {
      $message->subject('AP - Delete Record');
      $message->from('giligans.app@gmail.com', 'GI Alerts');
      $message->to('giligans.app@gmail.com');
      $message->cc('giligans.payables@gmail.com');
      $message->cc('jefferson.salunga@gmail.com');
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


