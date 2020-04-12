<?php namespace App\Listeners;

use Illuminate\Contracts\Mail\Mailer;
use App\Helpers\BossBranch;
use App\Helpers\Locator;

class EmploymentActivityEventListener
{

  private $mailer;
  private $bossBranch;
  private $fileStorage;

  public function __construct(Mailer $mailer, BossBranch $bossBranch) {
    $this->mailer = $mailer;
    $this->bossBranch = $bossBranch;
    $this->fileStorage = app()->fileStorage;
  }

  
  public function handleUpload($empActivity) {
    //test_log(json_encode($empActivity));
    
    $e = [];
    $am_email = app()->environment('production') ? $this->bossBranch->getFirstUser() : env('DEV_AM_MAIL');
    $csh_email = app()->environment('production') ? request()->user()->email : env('DEV_CSH_MAIL');

    // make uniform subject
    $subject = $empActivity->data['type'].' '.$empActivity->data['trail'].' '.$empActivity->data['manno'].' '.$empActivity->data['fullname'];
    

    // 1. notify the cashier
    $e['subject'] = 'EXREQ '.$subject;
    $e['to'] = $csh_email;
    $e['body'] = 'This is to notify that your export request has been sent to your RM/AM. Wait for the confirmation.';
    $this->mailer->send('emails.notifier', $e, function ($message) use ($e) {
      $message->subject($e['subject']);
      $message->from('giligans.app@gmail.com', 'Giligans HRIS');
      $message->to($e['to']);
      //$message->cc('giligans.hris@gmail.com');
    });

    // 2. email AM for approval with ETRF and ask confirmation.
    $e['subject'] = $empActivity->data['brcode'].' '.$subject;
    $e['to'] = $am_email;
    $e['replyTo'] = $csh_email;
    $e['attachment'] = NULL;
    $e['brcode'] = $empActivity->data['brcode'];
    $e['fullname'] = $empActivity->data['manno'].' '.$empActivity->data['fullname'];
    $e['cashier'] = $empActivity->data['cashier'];
    $e['notes'] = $empActivity->data['notes'];
    $e['link'] =  'http://gi-am.loc/employee/employment-activity/'.$empActivity->empActivity->lid();
    $e['logo'] = 'http://boss.giligansrestaurant.com/images/giligans-header.png';
    $e['href'] = 'http://gi-am.loc/timesheet/employee/'.$empActivity->empActivity->employee->lid();

    //$locator = new Locator('files');
    if ($this->fileStorage->exists($empActivity->data['file_path'])) 
      $e['attachment'] = $this->fileStorage->realFullPath($empActivity->data['file_path']);

    $this->mailer->send('emails.emp_activity.am_exreq_sent', $e, function ($message) use ($e) {
      $message->subject($e['subject']);
      $message->from('giligans.app@gmail.com', 'Giligans HRIS');
      $message->to($e['to']);
      $message->replyTo($e['replyTo'], 'Giligans '.$e['brcode']);
      //$message->cc('giligans.hris@gmail.com');
      
      if (!is_null($e['attachment']))
        $message->attach($e['attachment']);
    });





  }

  public function subscribe($events) {
    $events->listen(
      ['empActivity.upload'],
      'App\Listeners\EmploymentActivityEventListener@handleUpload'
    );

    $events->listen(
      'App\Events\Upload\ExportRequestSuccess',
      'App\Listeners\EmploymentActivityEventListener@handleUpload'
    );
  }
}


