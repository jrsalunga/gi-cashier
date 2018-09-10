<?php namespace App\Listeners;

use Illuminate\Contracts\Mail\Mailer;
use App\Http\Controllers\SetslpController as SetslpCtrl;

class SetslpEventListener
{

  private $mailer;
  private $setslpCtrl;

  public function __construct(Mailer $mailer, SetslpCtrl $setslpCtrl) {
    $this->mailer = $mailer;
    $this->setslpCtrl = $setslpCtrl;
  }

  
  public function handle($setslp) {
    $this->setslpCtrl->aggregate($setslp);
  }

  public function subscribe($events) {
    $events->listen(
      ['setslp.updated', 'setslp.created', 'setslp.deleted'],
      'App\Listeners\SetslpEventListener@handle'
    );
  }
}


