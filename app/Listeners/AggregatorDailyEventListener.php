<?php namespace App\Listeners;
use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\DailySales2Repository as DS;
use App\Repositories\Purchase2Repository as Purchase;
use App\Repositories\MonthProdcatRepository as Prodcat;
use App\Repositories\MonthGroupiesRepository as Groupies;
use App\Repositories\SalesmtdRepository as Salesmtd;

class AggregatorDailyEventListener
{

  private $mailer;
  private $ds;
  private $purchase;
  private $groupies;
  private $salesmtd;

  public function __construct(Mailer $mailer, DS $ds, Purchase $purchase, Groupies $groupies, Salesmtd $salesmtd) {
    $this->mailer = $mailer;
    $this->ds = $ds;
    $this->purchase = $purchase;
    $this->groupies = $groupies;
    $this->salesmtd = $salesmtd;
  }

  private function getRepo($table, $date, $branchid) {
    switch ($table) {
      case 'purchase':
        return $this->purchase->getDailySalesData($date, $branchid); // purchase data for DailySales
        break;
      case 'prodcat':
        return $this->salesmtd->aggregateProdcatByDr($fr, $to, $branchid);
        break;
      case 'groupies':
        return $this->salesmtd->aggregateGroupiesByDr($fr, $to, $branchid);
        break;
      default:
        throw new Exception("Table not found!", 1);
        break;
    }
    
  }

  public function aggregateDaily($event) {
    $table = strtolower($event->table);
    $datas = [];


    try {
      $datas = $this->getRepo($table, $event->date, $event->branchid);
    } catch (Exception $e) { 
      //
    }

    $this->saveData($table, $datas, $event->date, $event->branchid);
  }

  public function saveData($table, $datas, $date, $branchid) {
    switch ($table) {
      case 'purchase':
        $this->saveDSPurchaseData($datas, $date, $branchid);
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

  private function saveDSPurchaseData($datas, $date, $branchid) {
    $this->ds->firstOrNewField($datas, ['date', 'branchid']);
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
      'App\Events\Process\AggregatorDaily',
      'App\Listeners\AggregatorDailyEventListener@aggregateDaily'
    );

     $events->listen(
      'App\Events\Process\RankMonthlyProduct',
      'App\Listeners\AggregatorDailyEventListener@rankMonthlyProduct'
    );
  }

  
}


