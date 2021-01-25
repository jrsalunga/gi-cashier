<?php namespace App\Listeners;

use App\Models\DailySales;
use App\Models\Supplier;
use App\Models\Branch;
use Exception;
use Carbon\Carbon;
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

    // $this->mailer->queue('emails.backup-processsuccess', $data, function ($message) use ($event){
    //   $message->subject('Backup Upload');
    //   $message->from($event->user->email, $event->user->name.' ('.$event->user->email.')');
    //   $message->to('giligans.app@gmail.com');
    // });
  }

  public function onDailySalesSuccess($event) {

    $eom = $event->backup->filedate->copy()->subDay();
    if ($eom->copy()->endOfMonth()->format('Y-m-d') == $eom->format('Y-m-d')) {
      try {
        $this->computeMonthTotal($eom, $event->backup->branchid);
      } catch (Exception $e) {
        $this->emailError($event, $e->getMessage());
      }
    }
    
    try {
      $this->computeMonthTotal($event->backup->filedate, $event->backup->branchid);
    } catch (Exception $e) {
      $this->emailError($event, $e->getMessage());
    }
   
    try {
      $this->computeAllDailysalesTotal($event->backup->filedate->copy()->subDay());
    } catch (Exception $e) {
      $this->emailError($event, $e->getMessage());
    }

    try {
      $this->computeAllDailysalesTotal($event->backup->filedate);
    } catch (Exception $e) {
      $this->emailError($event, $e->getMessage());
    }

    // logAction('error: ', $e->getMessage());
    try {
      $this->computeAllMonthlysalesTotal($event->backup->filedate);
    } catch (Exception $e) {
      $this->emailError($event, $e->getMessage());
    }

    if ($eom->copy()->endOfMonth()->format('Y-m-d') == $eom->format('Y-m-d')) {
      try {
        $this->computeMonthTotal($eom, $event->backup->branchid);
      } catch (Exception $e) {
        $this->emailError($event, $e->getMessage());
      }
    }

  }

  private function computeMonthTotal(Carbon $date, $branchid) {

    $month = $this->ds->computeMonthTotal($date, $branchid);
  
    if (!is_null($month)) {
      $month['branch_id'] = $branchid;
      try {
        $this->ms->firstOrNewField(array_except($month->toArray(), ['year', 'month']), ['date', 'branch_id']);
      } catch (Exception $e) {
        throw new Exception("Error Processing BackupEventListener::computeMonthTotal", 1);
      }
      $this->ms->rank($month->date);
    }
  }

  private function computeAllDailysalesTotal(Carbon $date) {
    
    $ds = $this->ds->computeAllDailysalesTotal($date);
    
    if (!is_null($ds)) {
      $ds['branchid'] = 'ALL';

      try { 
        $this->ds->firstOrNewField(array_except($ds->toArray(), ['record_count', 'fc']), ['date', 'branchid']);
      } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
        throw new Exception("Error Processing BackupEventListener::computeAllDailysalesTotal", 1);
      }
    }
  }

  private function computeAllMonthlysalesTotal(Carbon $date) {

    $eom = $date->copy()->endOfMonth();
    $ms = $this->ms->computeAllMonthlysalesTotal($eom);
    
    if (!is_null($ms)) {
      $ms['branch_id'] = 'ALL';

      try { 
        $this->ms->firstOrNewField($ms->toArray(), ['date', 'branch_id']);
      } catch (Exception $e) {
        throw new Exception("Error Processing BackupEventListener::computeAllMonthlysalesTotal", 1);
      }
    }
  }

  private function emailError($event, $subject = 'Error') {
    $u = isset(request()->user()->name) ? request()->user()->name : 'bot';
    $data = [
      'user'      => $u,
      'cashier'   => $event->backup->cashier,
      'filename'  => $event->backup->filename,
      'body'      => 'Error onDailySalesSuccess '.$event->backup->branchid.' '.$event->backup->filedate,
    ];

    $this->mailer->queue('emails.notifier', $data, function ($message) use ($event, $subject){
      // $message->subject('BackupEventListener::computeAllDailysalesTotal');
      $message->subject($subject);
      $message->from($event->user->email, $event->user->name.' ('.$event->user->email.')');
      $message->to('giligans.app@gmail.com');
    });
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
        'supprefno' => 'XEM'.$data['date']->format('mdy'),
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

  public function processDeliveryFee($data) {
    $ds = DailySales::where('date', $data['date']->format('Y-m-d'))->where('branchid', $data['branch_id'])->first(['grabc', 'grab', 'panda', 'id']);

    if (abs($ds->grabc)>0) {

      $s = Supplier::firstOrCreate(['code'=>'GRBC', 'descriptor'=>'GRABFOOD']);
      $amt = $ds->grabc * config('gi-config.deliveryfee.grabc');

      $attrs = [
        'date'      => $data['date']->format('Y-m-d'),
        'componentid'=> '11EB228238760B969E0C14DDA9E4EAAF',
        'qty'       => 1,
        'ucost'     => $amt,
        'tcost'     => $amt,
        'terms'     => 'K',
        'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
        'supprefno' => 'XDF'.$data['date']->format('mdy'),
        'branchid'  => $data['branch_id'],
        'paytype'   => 1,
      ];

      try {
        $this->purchase->create($attrs);
      } catch(Exception $e) {
        throw $e;    
      }

      $ds->grabc_fee = $amt;
    }

    if (abs($ds->grab)>0) {

      $s = Supplier::firstOrCreate(['code'=>'GRBF', 'descriptor'=>'GRABFOOD']);
      $amt = $ds->grab * config('gi-config.deliveryfee.grab');

      $attrs = [
        'date'      => $data['date']->format('Y-m-d'),
        'componentid'=> '11EB228238760B969E0C14DDA9E4EAAF',
        'qty'       => 1,
        'ucost'     => $amt,
        'tcost'     => $amt,
        'terms'     => 'K',
        'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
        'supprefno' => 'XDF'.$data['date']->format('mdy'),
        'branchid'  => $data['branch_id'],
        'paytype'   => 1,
      ];

      try {
        $this->purchase->create($attrs);
      } catch(Exception $e) {
        throw $e;    
      }

      $ds->grab_fee = $amt;
    }

    if (abs($ds->panda)>0) {

      $s = Supplier::firstOrCreate(['code'=>'PAND', 'descriptor'=>'FOOD PANDA PHILIPPINES INC.']);
      $amt = $ds->panda * config('gi-config.deliveryfee.panda');

      $attrs = [
        'date'      => $data['date']->format('Y-m-d'),
        'componentid'=> '11EB228238760B969E0C14DDA9E4EAAF',
        'qty'       => 1,
        'ucost'     => $amt,
        'tcost'     => $amt,
        'terms'     => 'K',
        'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
        'supprefno' => 'XDF'.$data['date']->format('mdy'),
        'branchid'  => $data['branch_id'],
        'paytype'   => 1,
      ];

      try {
        $this->purchase->create($attrs);
      } catch(Exception $e) {
        throw $e;    
      }

      $ds->panda_fee = $amt;
    }

    $ds->totdeliver_fee = $ds->panda_fee + $ds->grab_fee + $ds->grabc_fee;

    try {
      $ds->save();
    } catch(Exception $e) {
      throw $e;    
    }
  }

  public function onDailySalesSuccess2($event) {
    
    try {
      $this->computeMonthTotal($event->date, $event->branchid);
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 1);
      throw new Exception("Error Processing BackupEventListener::onDailySalesSuccess2::computeMonthTotal", 1);
    }

    try {
      $this->computeAllDailysalesTotal($event->date);
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 1);
      throw new Exception("Error Processing BackupEventListener::onDailySalesSuccess2::computeAllDailysalesTotal", 1);
    }

    try {
      $this->computeAllMonthlysalesTotal($event->date);
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 1);
      throw new Exception("Error Processing BackupEventListener::onDailySalesSuccess2::computeAllMonthlysalesTotal", 1);
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

    $events->listen(
      'deliveryfee',
      'App\Listeners\BackupEventListener@processDeliveryFee'
    );
  }

  
}


