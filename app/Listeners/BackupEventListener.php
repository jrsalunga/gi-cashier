<?php namespace App\Listeners;

use App\Models\DailySales;
use App\Models\Supplier;
use App\Models\Branch;
use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\DailySales2Repository;
use App\Repositories\Purchase2Repository;
use App\Repositories\MonthlySalesRepository;

class BackupEventListener
{

  private $mailer;
  private $ds;
  private $ms;
  private $purchase;

  public function __construct(Mailer $mailer, DailySales2Repository $ds, MonthlySalesRepository $ms, Purchase2Repository $purchase) {
    $this->mailer = $mailer;
    $this->ds = $ds;
    $this->ms = $ms;
    $this->purchase = $purchase;
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
    } catch (Exception $e) {
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


  public function processEmpMeal($data) {
    $ds = DailySales::where('date', $data['date']->format('Y-m-d'))->where('branchid', $data['branch_id'])->first(['opex', 'emp_meal']);
    //$ds = $this->ds->findWhere(['date'=>'2018-08-31', 'branchid'=>'0C2D132F78A711E587FA00FF59FBB323'], ['opex', 'emp_meal'])->first();
    $s = Supplier::where(['code'=>$data['suppliercode'], 'branchid'=>$data['branch_id']])->first();
    if (is_null($s)) {
      $b = Branch::find($data['branch_id']);
      $s = Supplier::where(['code'=>$b->code])->first();
    }
    
    if (abs($ds->emp_meal)==0) {
      // skip
    } else {
      
      $attrs = [
        'date'        => $data['date']->format('Y-m-d'),
        'componentid' => '11E8BB3635ABF63DAEF21C1B0D85A7E0',
        'qty'       => 1,
        'ucost'     => $ds->emp_meal,
        'tcost'     => $ds->emp_meal,
        'terms'     => 'C',
        'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
        'supprefno' => $data['date']->format('mdy'),
        'branchid'  => $data['branch_id'],
        'paytype'   => 1,
      ];

      try {
        $this->purchase->create($attrs);
      } catch(Exception $e) {
        throw $e;    
      }
    }
  }


   public function onDailySalesSuccess2($event) {
    
    try {
      $month = $this->ds->computeMonthTotal($event->date, $event->branchid); // this will return NULL if the dailysales.sales = 0;
    } catch (Exception $e) {
      logAction('onDailySalesSuccess Error', $e->getMessage());
    } finally {
      if (!is_null($month)) {
              
      $this->ms->firstOrNewField(array_except($month->toArray(), ['year', 'month']), ['date', 'branch_id']);
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

    $events->listen(
      'App\Events\Backup\DailySalesSuccess2',
      'App\Listeners\BackupEventListener@onDailySalesSuccess2'
    );

    $events->listen(
      'transfer.empmeal',
      'App\Listeners\BackupEventListener@processEmpMeal'
    );
  }

  
}


