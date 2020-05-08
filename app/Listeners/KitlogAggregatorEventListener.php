<?php namespace App\Listeners;

use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\KitlogRepository as Kitlog;
use App\Repositories\Kitlog\DayAreaRepository as DayArea;
use App\Repositories\Kitlog\DayFoodRepository as DayFood;
use App\Repositories\Kitlog\MonthAreaRepository as MonthArea;
use App\Repositories\Kitlog\MonthFoodRepository as MonthFood;
use App\Repositories\Kitlog\DatasetAreaRepository as DatasetArea;
use App\Repositories\Kitlog\DatasetFoodRepository as DatasetFood;

class KitlogAggregatorEventListener
{

  private $mailer;
  private $kitlog;
  private $dayArea;
  private $dayFood;
  private $monthArea;
  private $monthFood;
  private $datasetArea;
  private $datasetFood;

  public function __construct(Mailer $mailer, DayArea $dayArea, DayFood $dayFood, MonthArea $monthArea, MonthFood $monthFood, DatasetArea $datasetArea, DatasetFood $datasetFood, Kitlog $kitlog) {
    $this->mailer = $mailer;
    $this->kitlog = $kitlog;
    $this->dayArea = $dayArea;
    $this->dayFood = $dayFood;
    $this->monthArea = $monthArea;
    $this->monthFood = $monthFood;
    $this->datasetArea = $datasetArea;
    $this->datasetFood = $datasetFood;
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
      case 'dataset_area':
        return is_null($branchid)
          ? $this->kitlog->aggregateAllAreaDatasetByDr($fr, $to)
          : $this->kitlog->aggregateBranchAreaDatasetByDr($fr, $to, $branchid);
        break;
      case 'dataset_food':
        return is_null($branchid)
          ? $this->kitlog->aggregateAllProductDatasetByDr($fr, $to)
          : $this->kitlog->aggregateBranchProductDatasetByDr($fr, $to, $branchid);
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

    // logAction($table, $event->branchid.' '.$to->format('Y-m-d'));

    try {
      $datas = $this->getRepo($table, $fr, $to, $event->branchid);
    } catch (Exception $e) {

      throw $e;
      
      if (app()->environment('local'))
        return dd($e->getMessage());
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
      case 'dataset_area':
        $this->saveDatasetArea($datas, $date, $branchid);
        break;
      case 'dataset_food':
        $this->saveDatasetFood($datas, $date, $branchid);
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

  private function saveDatasetFood($datas, $date, $branchid=NULL) {

    $ctr = 0;
    $data = [];
    $peak = $qty = 0;
    foreach ($datas as $k => $v) {
      $iscombo = $v->iscombo==1 ? 1 : 0;
      $idx = $v->product_id.'-'.$iscombo;
      if(array_key_exists($idx, $data)) {
        array_push($data[$idx]['dataset'], $v->grp.'|'.$v->txn.'|'.$v->qty);
      } else {
        $data[$idx]['product_id'] = $v->product_id;
        $data[$idx]['date'] = $v->date->format('Y-m-d');
        $data[$idx]['dataset'] = [$v->grp.'|'.$v->txn.'|'.$v->qty];
        $data[$idx]['peak'] = $v->peak;
        $data[$idx]['iscombo'] = $iscombo;
        $qty = $v->qty;
      }

      if ($qty < $v->qty) {
        $data[$idx]['peak'] = $v->peak;
        $qty = $v->qty;
      }
    }



    if (count($data)>0) {
      // return dd($data);
      $brid = is_null($branchid) ? 'all' : $branchid;
      $this->datasetFood->deleteWhere(['date'=>$date->format('Y-m-d'), 'branch_id'=>$brid]);

      if (is_null($branchid))
        $as = $this->kitlog->aggregateAllFoodByDr($date->copy()->startOfMonth(), $date->copy()->endOfMonth());
      else
        $as = $this->kitlog->aggregateFoodBranchByDr($date->copy()->startOfMonth(), $date->copy()->endOfMonth(), $branchid);

      foreach($as as $key => $a) {
        $iscombo = $a->iscombo==1 ? 1 : 0;
        $idx = $a->product_id.'-'.$iscombo;
        if(array_key_exists($idx, $data)) {

          $res = $this->datasetFood->create([
            'date' => $data[$idx]['date'],
            'branch_id' => $brid,
            'product_id' => $data[$idx]['product_id'],
            'dataset' => implode(",", $data[$idx]['dataset']),
            'qty' => $a->qty,
            'ave' => $a->ave,
            'peak' => $data[$idx]['peak'],
            'max' => $a->max,
            'min' => $a->min,
            'iscombo' => $iscombo,
            'rank' => ($ctr+1),
          ]);

          if (!is_null($res))
            $ctr++;
        }
      }
    }
    return $ctr;
  }

  private function saveDatasetArea($datas, $date, $branchid=NULL) {

    $ctr = 0;
    $data = [];
    $peak = $qty = 0;
    foreach ($datas as $k => $v) {
      if(array_key_exists($v->area, $data)) {
        array_push($data[$v->area]['dataset'], $v->grp.'|'.$v->txn.'|'.$v->qty);
      } else {
        $data[$v->area]['area'] = $v->area;
        $data[$v->area]['date'] = $v->date->format('Y-m-d');
        $data[$v->area]['dataset'] = [$v->grp.'|'.$v->txn.'|'.$v->qty];
        $data[$v->area]['peak'] = $v->peak;
        $qty = $v->qty;
      }
      
      if ($qty < $v->qty) {
        $data[$v->area]['peak'] = $v->peak;
        $qty = $v->qty;
      }
    }

    if (count($data)>0) {
      $brid = is_null($branchid) ? 'all' : $branchid;
      $this->datasetArea->deleteWhere(['date'=>$date->format('Y-m-d'), 'branch_id'=>$brid]);

      if (is_null($branchid))
        $as = $this->kitlog->aggregateAllAreaByDr($date->copy()->startOfMonth(), $date->copy()->endOfMonth());
      else
        $as = $this->kitlog->aggregateAreaBranchByDr($date->copy()->startOfMonth(), $date->copy()->endOfMonth(), $branchid);

      foreach($as as $key => $a) {
        if(array_key_exists($a->area, $data)) {
          $res = $this->datasetArea->create([
            'date' => $data[$a->area]['date'],
            'branch_id' => $brid,
            'area' => $data[$a->area]['area'],
            'dataset' => implode(",", $data[$a->area]['dataset']),
            'qty' => $a->qty,
            'ave' => $a->ave,
            'peak' => $data[$a->area]['peak'],
            'max' => $a->max,
            'min' => $a->min,
            'rank' => ($ctr+1),
          ]);

          if (!is_null($res))
            $ctr++;
        }
      }
    }
    return $ctr;
  }  

  public function subscribe($events) {
    $events->listen(
      'App\Events\Process\AggregatorKitlog',
      'App\Listeners\KitlogAggregatorEventListener@aggregate'
    );
  }
}


