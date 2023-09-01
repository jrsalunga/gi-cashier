<?php namespace App\Listeners;

use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\DailySales2Repository as DS;
use App\Repositories\Purchase2Repository as PR;
use App\Repositories\MonthlySalesRepository as MS;
use App\Repositories\MonthComponentRepository as MC;
use App\Repositories\MonthExpenseRepository as ME;
use App\Repositories\DayComponentRepository as DC;
use App\Repositories\DayExpenseRepository as DE;

class ProcessesEventListener
{

  private $mailer;
  private $ds;
  private $mc;
  private $ms;
  private $me;
  private $de;
  private $dc;
  private $purchase;

  public function __construct(Mailer $mailer, DS $ds, MS $ms, PR $purchase, MC $mc, ME $me, DE $de, DC $dc) {
    $this->mailer = $mailer;
    $this->ds = $ds;
    $this->ms = $ms;
    $this->mc = $mc;
    $this->me = $me;
    $this->de = $de;
    $this->dc = $dc;
    $this->purchase = $purchase;
  }

  

  public function aggregateComponentMonthly($event) {
    try {
      $month = $this->purchase->aggCompByDr($event->date->copy()->firstOfMonth(), $event->date->copy()->lastOfMonth(), $event->branchid);
    } catch (Exception $e) {
      /*
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
      */
    }

    return var_dump($month);

    foreach ($month as $key => $value) {

      $ord = isset($value->component->compcat->expense->ordinal)
      ? $value->component->compcat->expense->ordinal
      : 833;

      //$ord = 883;

      $this->mc->firstOrNewField([
        'date'          => $event->date->copy()->lastOfMonth()->format('Y-m-d'),
        'component_id'  => $value->componentid,
        'uom'           => $value->uom,
        // 'expensecode'   => 'xxxx',
        // 'expense_id'    => 'yyyy',
        'expensecode'   => $value->component->compcat->expense->code,
        'expense_id'    => $value->component->compcat->expenseid,
        'qty'           => $value->qty,
        'tcost'         => $value->tcost,
        'trans'         => $value->trans,
        'branch_id'     => $event->branchid,
        'status'        => $value->status,
        'ordinal'       => $ord,
      ], ['date', 'branch_id', 'component_id']);
      # code...
    }

  }

  public function aggregateMonthlyExpense($event) {
    try {
      $month = $this->purchase->aggExpByDr($event->date->copy()->firstOfMonth(), $event->date->copy()->lastOfMonth(), $event->branchid);
    } catch (Exception $e) {
      /*
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
      */
    }

    foreach ($month as $key => $value) {

      $sales_pct = 0;
      $ms = \App\Models\MonthlySales::where(['date'=>$event->date->copy()->lastOfMonth()->format('Y-m-d'), 'branch_id'=>$event->branchid], ['sales'])->first();

      if (!is_null($ms) && $ms->sales>0)
        $sales_pct = ($value->tcost/$ms->sales)*100;

      $this->me->firstOrNewField([
        'date'          => $event->date->copy()->lastOfMonth()->format('Y-m-d'),
        'expense_id'    => $value->expense_id,
        'qty'           => $value->qty,
        'tcost'         => $value->tcost,
        'trans'         => $value->trans,
        'sales_pct'     => $sales_pct,
        'branch_id'     => $event->branchid,
        'ordinal'       => $value->ordinal,
      ], ['date', 'branch_id', 'expense_id']);
      # code...
    }
  }

  public function aggregateComponentDaily($event) {
    try {
      $month = $this->purchase->aggCompByDr($event->date, $event->date, $event->branchid);
    } catch (Exception $e) {
      
    }

    foreach ($month as $key => $value) {

      $ord = isset($value->component->compcat->expense->ordinal)
      ? $value->component->compcat->expense->ordinal
      : 833;

      //$ord = 883;

      $this->dc->firstOrNewField([
        'date'          => $event->date->format('Y-m-d'),
        'component_id'  => $value->componentid,
        'expense_id'    => $value->component->compcat->expenseid,
        'qty'           => $value->qty,
        'tcost'         => $value->tcost,
        'trans'         => $value->trans,
        'branch_id'     => $event->branchid,
        'ordinal'       => $ord,
      ], ['date', 'branch_id', 'component_id']);
      # code...
    }
  }

  public function aggregateDailyExpense($event) {
    try {
      $month = $this->purchase->aggExpByDr($event->date, $event->date, $event->branchid);
    } catch (Exception $e) {
      
    }

    foreach ($month as $key => $value) {
      $this->de->firstOrNewField([
        'date'          => $event->date->format('Y-m-d'),
        'expense_id'    => $value->expense_id,
        'qty'           => $value->qty,
        'tcost'         => $value->tcost,
        'trans'         => $value->trans,
        'branch_id'     => $event->branchid,
        'ordinal'       => $value->ordinal,
      ], ['date', 'branch_id', 'expense_id']);
      # code...
    }

  }
  
  

  public function subscribe($events) {
    $events->listen(
      'App\Events\Process\AggregateComponentMonthly',
      'App\Listeners\ProcessesEventListener@aggregateComponentMonthly'
    );

    $events->listen(
      'App\Events\Process\AggregateComponentDaily',
      'App\Listeners\ProcessesEventListener@aggregateComponentDaily'
    );

    $events->listen(
      'App\Events\Process\AggregateMonthlyExpense',
      'App\Listeners\ProcessesEventListener@aggregateMonthlyExpense'
    );

    $events->listen(
      'App\Events\Process\AggregateDailyExpense',
      'App\Listeners\ProcessesEventListener@aggregateDailyExpense'
    );
  }

  
}


