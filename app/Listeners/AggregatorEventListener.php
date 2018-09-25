<?php namespace App\Listeners;

use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\MonthProductRepository as Product;
use App\Repositories\MonthProdcatRepository as Prodcat;
use App\Repositories\MonthGroupiesRepository as Groupies;
use App\Repositories\SalesmtdRepository as Salesmtd;

class AggregatorEventListener
{

  private $mailer;
  private $product;
  private $prodcat;
  private $groupies;
  private $salesmtd;

  public function __construct(Mailer $mailer, Product $product, Prodcat $prodcat, Groupies $groupies, Salesmtd $salesmtd) {
    $this->mailer = $mailer;
    $this->product = $product;
    $this->prodcat = $prodcat;
    $this->groupies = $groupies;
    $this->salesmtd = $salesmtd;
  }

  private function getRepo($table, $fr, $to, $branchid) {
    switch ($table) {
      case 'product':
        return $this->salesmtd->aggregateProductByDr($fr, $to, $branchid);
        break;
      case 'prodcat':
        return $this->salesmtd->aggregateProdcatByDr($fr, $to, $branchid);
        break;
      case 'groupies':
        return $this->salesmtd->aggregateGroupiesByDr($fr, $to, $branchid);
        break;
      default:
        throw new \Exception("Table not found!", 1);
        break;
    }
    
  }

  public function aggregateMonthly($event) {

    $table = strtolower($event->table);
    $datas = [];
    //return dd($this->getRepo($table));
    try {
      $datas = $this->getRepo($table, $event->date->copy()->firstOfMonth(), $event->date->copy()->lastOfMonth(), $event->branchid);
    } catch (\Exception $e) { 
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
      $this->prodcat->firstOrNewField([
        'date'          => $date->format('Y-m-d'),
        'prodcat_id'    => $value->prodcat_id,
        'qty'           => $value->qty,
        'sales'         => $value->sales,
        'trans'         => $value->trans,
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

  public function rankMonthlyProduct($event) {

    try {
      $datas = $this->product->rank($event->date->copy()->lastOfMonth(), $event->branchid);
    } catch (\Exception $e) { 
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


