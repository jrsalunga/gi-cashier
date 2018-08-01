<?php namespace App\Listeners;

use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\DailySales2Repository;
use App\Repositories\MonthlySalesRepository;

class BackupEventListener
{

  private $mailer;
  private $ds;
  private $ms;

  public function __construct(Mailer $mailer, DailySales2Repository $ds, MonthlySalesRepository $ms) {
    $this->mailer = $mailer;
    $this->ds = $ds;
    $this->ms = $ms;
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

  public function onDailySalesSuccess($event) {
    
    try {
      $month = $this->ds->computeMonthTotal($event->backup->filedate, $event->backup->branchid);
    } catch (\Exception $e) { 
      //logAction('onDailySalesSuccess Error', $e->getMessage());
      $data = [
        'user'      => request()->user()->name,
        'cashier'   => $event->backup->cashier,
        'filename'  => $event->backup->filename,
        'body'      => 'Error onDailySalesSuccess '.$event->backup->branchid.' '.$event->backup->filedate,
      ];

      $this->mailer->queue('emails.notifier', $data, function ($message) use ($event){
        $message->subject('Backup Upload DailySales Process Error');
        $message->from($event->user->email, $event->user->name.' ('.$event->user->email.')');
        $message->to('giligans.app@gmail.com');
      });
    
    } finally {
      if (!is_null($month)) {
        
      //logAction('onDailySalesSuccess', $event->backup->filedate->format('Y-m-d').' '.request()->user()->branchid.' '.json_encode($month));
      $this->ms->firstOrNewField(array_except($month->toArray(), ['year', 'month']), ['date', 'branch_id']);
      //logAction('onDailySalesSuccess', 'rank');
      $this->ms->rank($month->date);
      }
    }

  }
  

  public function subscribe($events) {
    $events->listen(
      'App\Events\Backup\ProcessSuccess',
      'App\Listeners\BackupEventListener@onProcessSuccess'
    );

    $events->listen(
      'App\Events\Backup\DailySalesSuccess',
      'App\Listeners\BackupEventListener@onDailySalesSuccess'
    );
  }

  
}


