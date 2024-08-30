<?php namespace App\Listeners;

use App\Helpers\Locator;
use App\Model\ApUpload;
use App\Helpers\BossBranch;
use Illuminate\Contracts\Mail\Mailer;

class AsrEventListener
{

  private $mailer;
  private $bossBranch;
  private $fileStorage;

  public function __construct(Mailer $mailer, BossBranch $bossBranch) {
    $this->mailer = $mailer;
    $this->bossBranch = $bossBranch;
    $this->fileStorage = app()->fileStorage;
  }


  public function notifyManagers($data) {

    $brcode = $data['brcode'];
    $date = $data['date'];
    $branchid = $data['branch_id'];

    $email_k = env('AP_K_EMAIL');
    $email_c = env('AP_C_EMAIL');
    $email_csh = app()->environment('production') ? request()->user()->email : env('DEV_CSH_MAIL');
    $e = [];
    if (app()->environment('production')) {
      $l = 'http://am.giligansrestaurant.com/asr/';
      $c = 'http://cashier.giligansrestaurant.com/'.strtolower($brcode).'/asr/';
      
      $rep = $this->bossBranch->getUsers();
      
      if (is_null($rep)) {
        $e['mailing_list'] = [
          ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
          ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
        ];
      } else {
        $e['mailing_list'] = [];
        foreach ($rep as $k => $u) {
          array_push($e['mailing_list'],
            [ 'name' => $u->name, 
              'email' => $u->email ]
          );
        }
      }
    } else {
      $l = 'http://gi-am.loc/ap/';
      $c = 'http://gi-cashier.loc/'.strtolower($brcode).'/apu/';
      $e['mailing_list'] = [
        ['name'=>'Jefferson Salunga', 'email'=>'jefferson.salunga@gmail.com'],
        ['name'=>'Jeff Salunga', 'email'=>'freakyash02@gmail.com'],
      ];
    }

    // $expl = explode('.', $event->model->filename);
    $e['attachment'] = [];
    $e['subject'] = $brcode.' EOD REPORTS '.$date->format('Ymd');    
    $filename1 = 'ASR_'.$brcode.'_'.$date->format('Ymd').'.PDF';
    $filename2 = 'ZREAD_'.$brcode.'_'.$date->format('Ymd').'.PDF';
    $filename3 = 'CSHAUDT_'.$brcode.'_'.$date->format('Ymd').'.PDF';

    $filepath = 'ASR'.DS.$date->format('Y').DS.$brcode.DS.$date->format('m').DS.$filename1;
    if ($this->fileStorage->exists($filepath)) 
      array_push($e['attachment'], $this->fileStorage->realFullPath($filepath));

    $filepath = 'ZREAD'.DS.$date->format('Y').DS.$brcode.DS.$date->format('m').DS.$filename2;
    if ($this->fileStorage->exists($filepath)) 
      array_push($e['attachment'], $this->fileStorage->realFullPath($filepath));

    $filepath = 'CSHAUDT'.DS.$date->format('Y').DS.$brcode.DS.$date->format('m').DS.$filename3;
    if ($this->fileStorage->exists($filepath)) 
      array_push($e['attachment'], $this->fileStorage->realFullPath($filepath));


    // email RM, AM, AHC 
    $e['to'] = env('AP_C_EMAIL');
    $e['replyTo'] = $email_csh;
    $e['link'] =  $l;
    $e['date'] = $date->format('m/d/Y');
    $this->mailer->queue('docu.asr.mail-notifier', $e, function ($message) use ($e) {
      $message->subject($e['subject']);
      $message->from('giligans.app@gmail.com', 'GI App');
      $message->replyTo($e['replyTo']);

      foreach ($e['mailing_list'] as $u)
        $message->to($u['email'], $u['name']);
        
      // $message->cc('giligans.log@gmail.com');

      if (count($e['attachment'])>0) 
        foreach($e['attachment'] as $value)
          $message->attach($value);
  

    });



  }

  public function subscribe($events) {
    $events->listen(
      'email-asr',
      'App\Listeners\AsrEventListener@notifyManagers'
    );
  }
}


