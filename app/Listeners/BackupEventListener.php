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
use App\Repositories\CashAuditRepository;

class BackupEventListener
{

  private $mailer;
  private $ds;
  private $ms;
  private $purchase;
  private $csh_audt;

  public function __construct(Mailer $mailer, DailySales2Repository $ds, MonthlySalesRepository $ms, Purchase2Repository $purchase, CashAuditRepository $csh_audt) {
    $this->mailer = $mailer;
    $this->ds = $ds;
    $this->ms = $ms;
    $this->purchase = $purchase;
    $this->csh_audt = $csh_audt;
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

    $eom = $event->backup->filedate->copy()->subDay();
    if ($eom->copy()->endOfMonth()->format('Y-m-d') == $eom->format('Y-m-d')) {
      try {
        $this->computeMonthTotal($eom, $event->backup->branchid);
      } catch (Exception $e) {
        $this->emailError($event, '1 '.$e->getMessage());
      }
    }
    
    try {
      $this->computeMonthTotal($event->backup->filedate, $event->backup->branchid);
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 1);
      $this->emailError($event, '2 '.$e->getMessage());
    }
   
    try {
      $this->computeAllDailysalesTotal($event->backup->filedate->copy()->subDay());
    } catch (Exception $e) {
      $this->emailError($event, '3 '.$e->getMessage());
    }

    try {
      $this->computeAllDailysalesTotal($event->backup->filedate);
    } catch (Exception $e) {
      $this->emailError($event, '4 '.$e->getMessage());
    }

    try {
      $this->computeAllMonthlysalesTotal($event->backup->filedate);
    } catch (Exception $e) {
      $this->emailError($event, '5 '.$e->getMessage());
    }

    if ($eom->copy()->endOfMonth()->format('Y-m-d') == $eom->format('Y-m-d')) {
      try {
        $this->computeMonthTotal($eom, $event->backup->branchid);
      } catch (Exception $e) {
        $this->emailError($event, '6 '.$e->getMessage());
      }
    }


    $this->updateEndingActualCash($event->backup->filedate, $event->backup->branchid);

  }

  private function computeMonthTotal(Carbon $date, $branchid) {

    $month = $this->ds->computeMonthTotal($date, $branchid);

  
    if (!is_null($month)) {
      $month['branch_id'] = $branchid;
      try {
        $this->ms->firstOrNewField(array_except($month->toArray(), ['year', 'month']), ['date', 'branch_id']);
      } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
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
        throw new Exception($e->getMessage(), 1);
        throw new Exception("Error Processing BackupEventListener::computeAllMonthlysalesTotal", 1);
      }
    }
  }

  public function updateEndingActualCash(Carbon $date, $branchid) {
    
    $data = [];
    $cshAudt = $this->csh_audt->scopeQuery(function($query) use ($date, $branchid) {
                    return $query->orderBy('date','desc')
                                ->where('branch_id', $branchid)
                                ->whereBetween('date', [$date->copy()->startOfMonth()->format('Y-m-d'), $date->copy()->endOfMonth()->format('Y-m-d')]);
                  })->first(['date', 'csh_cnt', 'branch_id']);

    if (!is_null($cshAudt)) {

      $data['branch_id'] = $branchid;
      $data['date'] = $date->copy()->endOfMonth()->format('Y-m-d');
      $data['ending_csh_date'] =  $cshAudt->date;
      $data['ending_csh'] =  $cshAudt->csh_cnt;

      try {
        $this->ms->firstOrNewField($data, ['date', 'branch_id']);
      } catch(Exception $e) {
        throw $e;    
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
      'error_msg'  => $subject,
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

    if (!is_null($ds)) {
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
          'uom'        => 'TRAN',
          'qty'       => 1,
          'ucost'     => $ds->emp_meal,
          'tcost'     => $ds->emp_meal,
          'terms'     => 'K',
          'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
          'supprefno' => 'XEM'.$data['date']->format('mdy'),
          'branchid'  => $data['branch_id'],
          'paytype'   => 4,
          'expensecode'=> 'EM',
          'expenseid' => 'F37344665CFA11E5ADBC00FF59FBB323',
        ];

        try {
          $this->purchase->create($attrs);
        } catch(Exception $e) {
          throw $e;    
        }
      }
    }
  }

  public function processDeliveryFee($data) {
    $ds = DailySales::where('date', $data['date']->format('Y-m-d'))->where('branchid', $data['branch_id'])->first(['grabc', 'grab', 'panda', 'zap', 'smo', 'maya', 'id']);

    test_log(json_encode($ds));

    if (!is_null($ds)) {

      if (abs($ds->grabc)>0) {

        $s = Supplier::firstOrCreate(['code'=>'GRBC', 'descriptor'=>'GRABFOOD']);
        $amt = $ds->grabc * config('gi-config.deliveryfee.grabc');

        $attrs = [
          'date'      => $data['date']->format('Y-m-d'),
          'componentid'=> '11EB228238760B969E0C14DDA9E4EAAF',
          'uom'        => 'TRAN',
          'qty'       => 1,
          'ucost'     => $amt,
          'tcost'     => $amt,
          'terms'     => 'H',
          'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
          'supprefno' => 'XDF'.$data['date']->format('mdy'),
          'branchid'  => $data['branch_id'],
          'paytype'   => 4, // Paid: Cheque - see gi-boss.config.giligans.php
          'expensecode'=> 'DF',
          'expenseid' => '11EAF712EB737D1B0DF08CC9CE4E9D4F',
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
          'uom'        => 'TRAN',
          'qty'       => 1,
          'ucost'     => $amt,
          'tcost'     => $amt,
          'terms'     => 'H',
          'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
          'supprefno' => 'XDF'.$data['date']->format('mdy'),
          'branchid'  => $data['branch_id'],
          'paytype'   => 4, // Paid: Cheque - see gi-boss.config.giligans.php
          'expensecode'=> 'DF',
          'expenseid' => '11EAF712EB737D1B0DF08CC9CE4E9D4F',
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
          'uom'        => 'TRAN',
          'qty'       => 1,
          'ucost'     => $amt,
          'tcost'     => $amt,
          'terms'     => 'H',
          'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
          'supprefno' => 'XDF'.$data['date']->format('mdy'),
          'branchid'  => $data['branch_id'],
          'paytype'   => 4, // Paid: Cheque - see gi-boss.config.giligans.php
          'expensecode'=> 'DF',
          'expenseid' => '11EAF712EB737D1B0DF08CC9CE4E9D4F',
        ];

        try {
          $this->purchase->create($attrs);
        } catch(Exception $e) {
          throw $e;    
        }

        $ds->panda_fee = $amt;
      }

      if (abs($ds->zap)>0) {

        $s = Supplier::firstOrCreate(['code'=>'ZAP', 'descriptor'=>'ZAP GROUP INC.']);
        $amt = $ds->zap * config('gi-config.deliveryfee.zap');

        $attrs = [
          'date'      => $data['date']->format('Y-m-d'),
          'componentid'=> '11EB228238760B969E0C14DDA9E4EAAF',
          'uom'        => 'TRAN',
          'qty'       => 1,
          'ucost'     => $amt,
          'tcost'     => $amt,
          'terms'     => 'H',
          'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
          'supprefno' => 'XDF'.$data['date']->format('mdy'),
          'branchid'  => $data['branch_id'],
          'paytype'   => 4, // Paid: Cheque - see gi-boss.config.giligans.php
          'expensecode'=> 'DF',
          'expenseid' => '11EAF712EB737D1B0DF08CC9CE4E9D4F',
        ];

        try {
          $this->purchase->create($attrs);
        } catch(Exception $e) {
          throw $e;    
        }

        $ds->zap_fee = $amt;
      }


      if (abs($ds->smo)>0) {

        $s = Supplier::firstOrCreate(['code'=>'SMO', 'descriptor'=>'SM PRIME HOLDINGS.']);

        // test_log('smo pct: '.config('gi-config.deliveryfee.smo'));
        $amt = $ds->smo * config('gi-config.deliveryfee.smo');
        // $amt = $ds->smo;
        // test_log('smo amt: '.$amt);

        $attrs = [
          'date'      => $data['date']->format('Y-m-d'),
          'componentid'=> '11EB228238760B969E0C14DDA9E4EAAF',
          'uom'        => 'TRAN',
          'qty'       => 1,
          'ucost'     => $amt,
          'tcost'     => $amt,
          'terms'     => 'H',
          'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
          'supprefno' => 'XDF'.$data['date']->format('mdy'),
          'branchid'  => $data['branch_id'],
          'paytype'   => 4, // Paid: Cheque - see gi-boss.config.giligans.php
          'expensecode'=> 'DF',
          'expenseid' => '11EAF712EB737D1B0DF08CC9CE4E9D4F',
        ];

        try {
          $this->purchase->create($attrs);
        } catch(Exception $e) {
          throw $e;    
        }

        $ds->smo_fee = $amt;
      }


      if (abs($ds->maya)>0) {

        $s = Supplier::firstOrCreate(['code'=>'MAYA', 'descriptor'=>'MAYA PHILIPPINES, INC.']);
        
        // test_log('maya pct: '.config('gi-config.deliveryfee.maya'));
        $amt = $ds->maya * config('gi-config.deliveryfee.maya');
        // $amt = $ds->maya;
        // test_log('maya amt: '.$amt);

        $attrs = [
          'date'      => $data['date']->format('Y-m-d'),
          'componentid'=> '11EE1B2F870FB663CCD5A1EF7D6E8E7E', // COMMISSION
          'uom'        => 'TRAN',
          'qty'       => 1,
          'ucost'     => $amt,
          'tcost'     => $amt,
          'terms'     => 'H',
          'supplierid'=> is_null($s) ? $data['branch_id'] : $s->id,
          'supprefno' => 'XDF'.$data['date']->format('mdy'),
          'branchid'  => $data['branch_id'],
          'paytype'   => 4, // Paid: Cheque - see gi-boss.config.giligans.php
          'expensecode'=> 'DF',
          'expenseid' => '11EAF712EB737D1B0DF08CC9CE4E9D4F',
        ];

        try {
          $this->purchase->create($attrs);
        } catch(Exception $e) {
          throw $e;    
        }

        $ds->maya_fee = $amt;
      }

      $ds->totdeliver_fee = $ds->panda_fee + $ds->grab_fee + $ds->grabc_fee + $ds->zap_fee + $ds->smo_fee + $ds->maya_fee;

      try {
        $ds->save();
      } catch(Exception $e) {
        throw $e;    
      }

    } // !is_null($ds)
  }


  public function processDirectProfit($data) {
    $ds = DailySales::where('date', $data['date']->format('Y-m-d'))->where('branchid', $data['branch_id'])->first(['sales', 'cos', 'ncos', 'transcost', 'opex', 'emp_meal', 'totdeliver_fee', 'id']);

    if (!is_null($ds)) {

      $opex = $ds->opex + $ds->emp_meal + $ds->totdeliver_fee;
      $cog = ($ds->cos + $ds->ncos) - $ds->transcost;

      $ds->profit_direct = ($ds->sales - ($cog + $opex));

      try {
        $ds->save();
      } catch(Exception $e) {
        throw $e;    
      }
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

    $events->listen(
      'direct-profit',
      'App\Listeners\BackupEventListener@processDirectProfit'
    );
  }

  
}


