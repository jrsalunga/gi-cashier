<?php namespace App\Listeners;

use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\MonthProductRepository as Product;
use App\Repositories\MonthProdcatRepository as Prodcat;
use App\Repositories\MonthGroupiesRepository as Groupies;
use App\Repositories\SalesmtdRepository as Salesmtd;
use App\Repositories\StockTransferRepository as Transfer;
use App\Repositories\MonthExpenseRepository as ME;
use App\Repositories\ChangeItemRepository as ChangeItem;

class AggregatorEventListener
{

  private $mailer;
  private $product;
  private $prodcat;
  private $groupies;
  private $salesmtd;
  private $transfer;
  private $changeItem;
  private $me;

  public function __construct(Mailer $mailer, Product $product, Prodcat $prodcat, Groupies $groupies, Salesmtd $salesmtd, Transfer $transfer, ChangeItem $changeItem, ME $me) {
    $this->mailer = $mailer;
    $this->product = $product;
    $this->prodcat = $prodcat;
    $this->groupies = $groupies;
    $this->salesmtd = $salesmtd;
    $this->transfer = $transfer;
    $this->changeItem = $changeItem;
    $this->me = $me;
  }

  private function getRepo($table, $fr, $to, $branchid) {
    switch ($table) {
      case 'product':
        return $this->salesmtd->aggregateProductByDr($fr, $to, $branchid);
        break;
      case 'prodcat':
        return $this->salesmtd->aggregateProdcatByDr2($fr, $to, $branchid);
        break;
      case 'groupies':
        return $this->salesmtd->aggregateGroupiesByDr($fr, $to, $branchid);
        break;
      case 'trans-expense':
        return $this->transfer->aggExpByDr($fr, $to, $branchid);
        break;
      case 'change_item':
        return $this->changeItem->aggregateGroupiesByDr($fr, $to, $branchid);
        break;
      default:
        throw new Exception("Table not found!", 1);
        break;
    }
    
  }

  public function aggregateMonthly($event) {

    $table = strtolower($event->table);
    $datas = [];
      // return dd($this->getRepo($table));
      // $datas = $this->getRepo($table, $event->date->copy()->firstOfMonth(), $event->date->copy()->lastOfMonth(), $event->branchid);
    try {
      $datas = $this->getRepo($table, $event->date->copy()->firstOfMonth(), $event->date->copy()->lastOfMonth(), $event->branchid);
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

    $this->saveData($table, $datas, $event->date->copy()->lastOfMonth(), $event->branchid);

  }

  public function saveData($table, $datas, $date, $branchid) {
    switch ($table) {
      case 'product':
        $this->saveProduct($datas, $date, $branchid);
        break;
      case 'prodcat':
        $this->saveProdcat($datas, $date, $branchid);
        break;
      case 'groupies':
        $this->saveGroupies($datas, $date, $branchid);
        break;
      case 'trans-expense':
        $this->saveTransExpense($datas, $date, $branchid);
        break;
      case 'change_item':
        $this->saveGroupiesChangeItem($datas, $date, $branchid);
        break;
      default:
        
        break;
    }

  }

  private function saveProduct($datas, $date, $branchid) {
    foreach ($datas as $key => $value) {
      $this->product->firstOrNewField([
        'date'          => $date->format('Y-m-d'),
        'product_id'    => $value->product_id,
        'qty'           => $value->qty,
        'netamt'        => $value->netamt,
        'trans'         => $value->trans,
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'product_id']);
    }
  }

  private function saveProdcat($datas, $date, $branchid) {
    foreach ($datas as $key => $value) {

      if (empty($value->prodcat_id)) 
        $prodcat_id = app()->environment('local') ? '625E2E18BDF211E6978200FF18C615EC' : 'E841F22BBC3711E6856EC3CDBB4216A7';
      else 
        $prodcat_id = $value->prodcat_id;

      $this->prodcat->firstOrNewField([
        'date'          => $date->format('Y-m-d'),
        'prodcat_id'    => $prodcat_id,
        'qty'           => $value->qty,
        'sales'         => $value->sales,
        'trans'         => $value->trans,
        'pct'           => $value->pct,
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'prodcat_id']);
    }
  }

  private function saveGroupies($datas, $date, $branchid) {
    foreach ($datas as $key => $value) {
      $this->groupies->firstOrNewField([
        'date'          => $date->format('Y-m-d'),
        'code'          => $value->code,
        'qty'           => $value->qty,
        'netamt'        => $value->netamt,
        'trans'         => $value->trans,
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'code']);
    }
  }

  private function saveTransExpense($datas, $date, $branchid) {
    foreach ($datas as $key => $value) {
      $this->me->firstOrNewField([
        'date'          => $date->format('Y-m-d'),
        'xfred'         => $value->tcost,
        'expense_id'    => $value->expense_id,
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'expense_id']);
    }
  }

  private function saveGroupiesChangeItem($datas, $date, $branchid) {
    //return dd($datas->toArray());
    foreach ($datas as $key => $value) {
      $this->groupies->firstOrNewField([
        'date'          => $date->format('Y-m-d'),
        'code'          => $value->code,
        'change_item'   => $value->change_item,
        'diff'          => $value->diff,
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'code']);
    }
  }

  public function rankMonthlyProduct($event) {

    try {
      $datas = $this->product->rank($event->date->copy()->lastOfMonth(), $event->branchid);
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
  }
  
  

  public function subscribe($events) {
    $events->listen(
      'App\Events\Process\AggregatorMonthly',
      'App\Listeners\AggregatorEventListener@aggregateMonthly'
    );

     $events->listen(
      'App\Events\Process\RankMonthlyProduct',
      'App\Listeners\AggregatorEventListener@rankMonthlyProduct'
    );
  }

  
}


