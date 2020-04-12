<?php namespace App\Listeners;

use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\KitlogRepository as Kitlog;
use App\Repositories\Kitlog\DayAreaRepository as DayArea;
use App\Repositories\Kitlog\DayFoodRepository as DayFood;
use App\Repositories\Kitlog\MonthAreaRepository as MonthArea;
use App\Repositories\Kitlog\MonthFoodRepository as MonthFood;

class KitlogAggregatorEventListener
{

  private $mailer;
  private $dayArea;
  private $dayFood;
  private $monthArea;
  private $monthFood;
  private $kitlog;

  public function __construct(Mailer $mailer, DayArea $dayArea, DayFood $dayFood, MonthArea $monthArea, MonthFood $monthFood, Kitlog $kitlog) {
    $this->mailer = $mailer;
    $this->dayArea = $dayArea;
    $this->dayFood = $dayFood;
    $this->monthArea = $monthArea;
    $this->monthFood = $monthFood;
    $this->kitlog = $kitlog;
  }

  private function getRepo($table, $fr, $to, $branchid) {
    switch ($table) {
      case 'day_kitlog_area':
        return $this->kitlog->aggregateAreaBranchByDr($fr, $to, $branchid);
        break;
      case 'day_kitlog_food':
        return $this->kitlog->aggregateFoodBranchByDr($fr, $to, $branchid);
        break;
      case 'month_kitlog_area':
        return $this->kitlog->aggregateAreaBranchByDr($fr, $to, $branchid);
        break;
      case 'month_kitlog_food':
        return $this->kitlog->aggregateFoodBranchByDr($fr, $to, $branchid);
        break;
      default:
        throw new Exception("Table not found!", 1);
        break;
    }
  }

  public function aggregate($event) {

    $table = strtolower($event->table);
    $datas = [];

    $explode = explode('_', $event->table);

    if ($explode[0]=='day') {
      $fr = $event->date;
      $to = $event->date;
    } else {
      $fr = $event->date->copy()->firstOfMonth();
      $to = $event->date->copy()->lastOfMonth();
    }

    try {
      $datas = $this->getRepo($table, $fr, $to, $event->branchid);
    } catch (Exception $e) {
      
      if (app()->environment('local'))
        logAction('onDailySalesSuccess Error', $e->getMessage());
      
      $data = [
        'user'      => request()->user()->name,
        'cashier'   => $event->backup->cashier,
        'filename'  => $event->backup->filename,
        'body'      => 'Error KitlogAggregatorEventListener '.$event->backup->branchid.' '.$event->backup->filedate,
      ];

      $this->mailer->queue('emails.notifier', $data, function ($message) use ($event){
        $message->subject('KitlogAggregatorEventListener Error');
        $message->from($event->user->email, $event->user->name.' ('.$event->user->email.')');
        $message->to('jefferson.salunga@gmail.com');
      });
      
    }

    // if (app()->environment('local')) {
    //   if (count($datas)>0) {
    //     foreach ($datas as $key => $v) {
    //       logAction($key, $v['qty'].'-'.$v['ave'].'-'.$v['max'].'-'.$v['min']);
    //     }
    //   }
    // }

    $this->saveData($table, $datas, $to, $event->branchid);
  }

  public function saveData($table, $datas, $date, $branchid) {
    switch ($table) {
      case 'day_kitlog_food':
        $this->saveDayFood($datas, $date, $branchid);
        break;
      case 'month_kitlog_food':
        $this->saveMonthFood($datas, $date, $branchid);
        break;
      case 'day_kitlog_area':
        $this->saveDayArea($datas, $date, $branchid);
        break;
      case 'month_kitlog_area':
        $this->saveMonthArea($datas, $date, $branchid);
        break;
      default:
        
        break;
    }
  }

  private function saveDayFood($datas, $date, $branchid) {
    $this->dayFood->deleteWhere(['date'=>$date->format('Y-m-d')]);
    foreach ($datas as $key => $value) {
      $this->dayFood->firstOrNewField([
        'date'       => $date->format('Y-m-d'),
        'branch_id'  => $branchid,
        'product_id' => $value->product_id,
        'qty'        => $value->qty,
        'ave'        => $value->ave,
        'max'        => $value->max,
        'min'        => $value->min,
        // 'stime'      => $value->stime,
        'area'       => $value->area,
        'iscombo'    => $value->iscombo,
        'rank'       => ($key+1),
      ], ['date', 'branch_id', 'product_id', 'iscombo']);
    }
  }

  private function saveMonthFood($datas, $date, $branchid) {
    foreach ($datas as $key => $value) {
      $this->monthFood->firstOrNewField([
        'date'       => $date->format('Y-m-d'),
        'branch_id'  => $branchid,
        'product_id' => $value->product_id,
        'qty'        => $value->qty,
        'ave'        => $value->ave,
        'max'        => $value->max,
        'min'        => $value->min,
        // 'stime'      => $value->stime,
        'area'       => $value->area,
        'iscombo'    => $value->iscombo,
        'rank'       => ($key+1),
      ], ['date', 'branch_id', 'product_id', 'iscombo']);
    }
  }

  private function saveDayArea($datas, $date, $branchid) {
    $this->dayArea->deleteWhere(['date'=>$date->format('Y-m-d')]);
    foreach ($datas as $key => $value) {
      $this->dayArea->firstOrNewField([
        'date'       => $date->format('Y-m-d'),
        'branch_id'  => $branchid,
        'qty'        => $value->qty,
        'ave'        => $value->ave,
        'max'        => $value->max,
        'min'        => $value->min,
        // 'stime'      => $value->stime,
        'area'       => $value->area,
        // 'iscombo'    => $value->iscombo,
        'rank'       => ($key+1),
      ], ['date', 'branch_id', 'area']);
    }
  }

  private function saveMonthArea($datas, $date, $branchid) {
    $this->monthArea->deleteWhere(['date'=>$date->format('Y-m-d')]);
    foreach ($datas as $key => $value) {
      $this->monthArea->firstOrNewField([
        'date'       => $date->format('Y-m-d'),
        'branch_id'  => $branchid,
        'qty'        => $value->qty,
        'ave'        => $value->ave,
        'max'        => $value->max,
        'min'        => $value->min,
        // 'stime'      => $value->stime,
        'area'       => $value->area,
        // 'iscombo'    => $value->iscombo,
        'rank'       => ($key+1),
      ], ['date', 'branch_id', 'area']);
    }
  }

  
  
  

  public function subscribe($events) {
    $events->listen(
      'App\Events\Process\AggregatorKitlog',
      'App\Listeners\KitlogAggregatorEventListener@aggregate'
    );

    
  }

  
}


