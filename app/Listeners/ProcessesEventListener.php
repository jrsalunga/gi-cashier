<?php namespace App\Listeners;

use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\DailySales2Repository as DS;
use App\Repositories\Purchase2Repository as PR;
use App\Repositories\MonthlySalesRepository as MS;
use App\Repositories\MonthComponentRepository as MC;
use App\Repositories\MonthExpenseRepository as ME;

class ProcessesEventListener
{

  private $mailer;
  private $ds;
  private $mc;
  private $ms;
  private $me;
  private $purchase;

  public function __construct(Mailer $mailer, DS $ds, MS $ms, PR $purchase, MC $mc, ME $me) {
    $this->mailer = $mailer;
    $this->ds = $ds;
    $this->ms = $ms;
    $this->mc = $mc;
    $this->me = $me;
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

    foreach ($month as $key => $value) {

      $ord = isset($value->component->compcat->expense->ordinal)
      ? $value->component->compcat->expense->ordinal
      : 833;

      //$ord = 883;

      $this->mc->firstOrNewField([
        'date'          => $event->date->copy()->lastOfMonth()->format('Y-m-d'),
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
      $this->me->firstOrNewField([
        'date'          => $event->date->copy()->lastOfMonth()->format('Y-m-d'),
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
      'App\Events\Process\AggregateMonthlyExpense',
      'App\Listeners\ProcessesEventListener@aggregateMonthlyExpense'
    );
  }

  
}


