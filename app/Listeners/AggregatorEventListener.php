<?php namespace App\Listeners;

use Exception;
use Illuminate\Contracts\Mail\Mailer;
use App\Repositories\MonthProductRepository as Product;
use App\Repositories\MonthProdcatRepository as Prodcat;
use App\Repositories\MonthGroupiesRepository as Groupies;
use App\Repositories\SalesmtdRepository as Salesmtd;
use App\Repositories\StockTransferRepository as Transfer;
use App\Repositories\MonthExpenseRepository as ME;
use App\Repositories\DayExpenseRepository as DE;
use App\Repositories\ChangeItemRepository as ChangeItem;
use App\Repositories\DayProdcatRepository as DayProdcat;
use App\Repositories\CashAuditRepository as CashAudit;
use App\Repositories\MonthCashAuditRepository as MonthCashAudit;
use App\Repositories\ChargesRepository as Charges;
use App\Repositories\MonthChargeTypeRepository as MChargeType;
use App\Repositories\MonthCardTypeRepository as MCardType;
use App\Repositories\MonthSaleTypeRepository as MSalesType;


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
  private $de;
  private $dp;
  private $cashAudit;
  private $mCashAudit;
  private $charges;
  private $mChargeType;
  private $mCardType;
  private $mSalesType;

  public function __construct(Mailer $mailer, Product $product, Prodcat $prodcat, Groupies $groupies, Salesmtd $salesmtd, Transfer $transfer, ChangeItem $changeItem, ME $me, DE $de, DayProdcat $dp, CashAudit $cashAudit, MonthCashAudit $mCashAudit, Charges $charges, MChargeType $mChargeType, MCardType $mCardType, MSalesType $mSalesType) {
    $this->mailer = $mailer;
    $this->product = $product;
    $this->prodcat = $prodcat;
    $this->groupies = $groupies;
    $this->salesmtd = $salesmtd;
    $this->transfer = $transfer;
    $this->changeItem = $changeItem;
    $this->me = $me;
    $this->de = $de;
    $this->dp = $dp;
    $this->cashAudit = $cashAudit;
    $this->mCashAudit = $mCashAudit;
    $this->charges = $charges;
    $this->mChargeType = $mChargeType;
    $this->mCardType = $mCardType;
    $this->mSalesType = $mSalesType;
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
      case 'cash_audit':
        return $this->cashAudit->aggregateByDr($fr, $to, $branchid);
        break;
      case 'charge-type':
        return $this->charges->aggregateChargeTypeByDr($fr, $to, $branchid);
        break;
      case 'sale-type':
        return $this->charges->aggregateSaleTypeByDr($fr, $to, $branchid);
        break;
      case 'card-type':
        return $this->charges->aggregateCardTypeByDr($fr, $to, $branchid);
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

    $this->saveData($table, $datas, $event->date, $event->branchid);

  }

  public function saveData($table, $datas, $date, $branchid) {
    switch ($table) {
      case 'product':
        $this->saveProduct($datas, $date->copy()->lastOfMonth(), $branchid);
        break;
      case 'prodcat':
        $this->saveProdcat($datas, $date->copy()->lastOfMonth(), $branchid);
        break;
      case 'groupies':
        $this->saveGroupies($datas, $date->copy()->lastOfMonth(), $branchid);
        break;
      case 'trans-expense':
        $this->saveTransExpense($datas, $date->copy()->lastOfMonth(), $branchid);
        break;
      case 'change_item':
        $this->saveGroupiesChangeItem($datas, $date->copy()->lastOfMonth(), $branchid);
        break;
      case 'cash_audit':
        $this->saveCashAudit($datas, $date->copy()->lastOfMonth(), $branchid);
        break;
      case 'charge-type':
        $this->saveChargeType($datas, $date, $branchid);
        break;
      case 'sale-type':
        $this->saveSaleType($datas, $date, $branchid);
        break;
      case 'card-type':
        $this->saveCardType($datas, $date, $branchid);
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
        'date'          => $date->copy()->lastOfMonth()->format('Y-m-d'),
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
        'date'          => $date->copy()->lastOfMonth()->format('Y-m-d'),
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

      $ex = \App\Models\Expense::find($value->expense_id);

      if(is_null($ex)) {
        $ord = 833;
        $expense_id = 'F37A72215CFA11E5ADBC00FF59FBB323';
      } else {
        $ord = $ex->ordinal;
        $expense_id = $value->expense_id;
      }

      $sales_pct = 0;
      $ms = \App\Models\MonthlySales::where(['date'=>$date->copy()->lastOfMonth()->format('Y-m-d'), 'branch_id'=>$branchid], ['sales'])->first();

      if (!is_null($ms) && $ms->sales>0) {
        $me = \App\Models\MonthExpense::where(['date'=>$date->copy()->lastOfMonth()->format('Y-m-d'), 'expense_id'=>$value->expense_id, 'branch_id'=>$branchid])->first();
        $sales_pct = (($me->tcost-$value->tcost)/$ms->sales)*100;
      }


      $this->me->firstOrNewField([
        'date'          => $date->copy()->lastOfMonth()->format('Y-m-d'),
        'xfred'         => $value->tcost,
        'sales_pct'     => $sales_pct,
        'expense_id'    => $expense_id,
        'branch_id'     => $branchid,
        'ordinal'       => $ord,
      ], ['date', 'branch_id', 'expense_id']);
    }
  }

  private function saveGroupiesChangeItem($datas, $date, $branchid) {
    //return dd($datas->toArray());
    foreach ($datas as $key => $value) {
      $this->groupies->firstOrNewField([
        'date'          => $date->copy()->lastOfMonth()->format('Y-m-d'),
        'code'          => $value->code,
        'change_item'   => $value->change_item,
        'diff'          => $value->diff,
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'code']);
    }
  }

  private function saveCashAudit($datas, $date, $branchid) {

    $attr = [];
    $attr['date'] = $date->copy()->lastOfMonth()->format('Y-m-d');
    $attr['branch_id'] = $branchid;

    if (count(collect($datas))>0)
      foreach ($datas->toArray() as $k => $value)
        $attr[$k] = $value;

    return $this->mCashAudit->firstOrNewField($attr, ['date', 'branch_id']);
  }

  private function saveChargeType($datas, $date, $branchid) {

    $eom = $date->copy()->lastOfMonth();
    $c = ['CASH', 'BDO', 'BANKARD', 'GRAB', 'GRABC', 'PANDA'];

    if (in_array($date->format('d'), [5, 10, 15, 20, 25]) || $eom->format('Y-m-d')==$date->format('Y-m-d'))
      $this->mChargeType->deleteWhere(['branch_id'=>$branchid, 'date'=>$eom->format('Y-m-d')]);

    foreach ($datas as $key => $value) {
      $k = array_search($value->chrg_type, $c);
      $this->mChargeType->firstOrNewField([
        'date'          => $date->copy()->lastOfMonth()->format('Y-m-d'),
        'chrg_type'     => $value->chrg_type,
        'total'         => $value->total,
        'txn'           => $value->txn,
        'pct'           => $value->pct,
        'ordinal'       => is_null($k) ? 99 : ($k+1),
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'chrg_type']);
    }
  }

  private function saveSaleType($datas, $date, $branchid) {

    $eom = $date->copy()->lastOfMonth();
    $c = ['DINEIN', 'TKEOUT', 'CALPUP', 'CALWED', 'ONLRID', 'ONLCUS', 'ONLWED', 'FUNCTN', 'BULKOR', 'CATERG', 'OTHERS'];

    if (in_array($date->format('d'), [5, 10, 15, 20, 25]) || $eom->format('Y-m-d')==$date->format('Y-m-d'))
      $this->mSalesType->deleteWhere(['branch_id'=>$branchid, 'date'=>$eom->format('Y-m-d')]);
    
    foreach ($datas as $key => $value) {
      $k = array_search($value->saletype, $c);
      $this->mSalesType->firstOrNewField([
        'date'          => $eom->format('Y-m-d'),
        'saletype'      => $value->saletype,
        'total'         => $value->total,
        'txn'           => $value->txn,
        'pct'           => $value->pct,
        'ordinal'       => is_null($k) ? 99 : ($k+1),
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'saletype']);
    }
  }

  private function saveCardType($datas, $date, $branchid) {

    $eom = $date->copy()->lastOfMonth();
    $c = ['CASH', 'MASTER', 'VISA', 'AMEX', 'JCB', 'DINERS', 'OTHERS'];

    if (in_array($date->format('d'), [5, 10, 15, 20, 25]) || $eom->format('Y-m-d')==$date->format('Y-m-d'))
      $this->mCardType->deleteWhere(['branch_id'=>$branchid, 'date'=>$eom->format('Y-m-d')]);
    
    foreach ($datas as $key => $value) {

      $x = empty($value->card_type) ? $value->terms : $value->card_type;
      $k = array_search($x, $c);
      
      $this->mCardType->firstOrNewField([
        'date'          => $eom->format('Y-m-d'),
        'cardtype'      => $x,
        'total'         => $value->total,
        'txn'           => $value->txn,
        'pct'           => $value->pct,
        'ordinal'       => is_null($k) ? 99 : ($k+1),
        'branch_id'     => $branchid,
      ], ['date', 'branch_id', 'cardtype']);
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



  public function aggregateDaily($event) {

    $table = strtolower($event->table);
    $datas = [];

    try {
      $datas = $this->getRepo($table, $event->date, $event->date, $event->branchid);
    } catch (Exception $e) {

    }

    $this->saveDailyData($table, $datas, $event->date, $event->branchid);
  }

  private function saveDayTransExpense($datas, $date, $branchid) {
    foreach ($datas as $key => $value) {

      $ex = \App\Models\Expense::find($value->expense_id);

      $ord = is_null($ex)
      ? 833
      : $ex->ordinal;

      $this->de->firstOrNewField([
        'date'          => $date->format('Y-m-d'),
        'xfred'         => $value->tcost,
        'expense_id'    => $value->expense_id,
        'branch_id'     => $branchid,
        'ordinal'       => $ord,
      ], ['date', 'branch_id', 'expense_id']);
    }
  }

  private function saveDayProdcat($datas, $date, $branchid) {
    foreach ($datas as $key => $value) {

      if (empty($value->prodcat_id)) 
        $prodcat_id = app()->environment('local') ? '625E2E18BDF211E6978200FF18C615EC' : 'E841F22BBC3711E6856EC3CDBB4216A7';
      else 
        $prodcat_id = $value->prodcat_id;

      $this->dp->firstOrNewField([
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

  public function saveDailyData($table, $datas, $date, $branchid) {
    switch ($table) {
      // case 'product':
      //   $this->saveDayProduct($datas, $date, $branchid);
      //   break;
      case 'prodcat':
         $this->saveDayProdcat($datas, $date, $branchid);
         break;
      // case 'groupies':
      //   $this->saveDayGroupies($datas, $date, $branchid);
      //   break;
      case 'trans-expense':
        $this->saveDayTransExpense($datas, $date, $branchid);
        break;
      // case 'change_item':
      //   $this->saveDayGroupiesChangeItem($datas, $date, $branchid);
      //   break;
      default:
        break;
    }
  }


  
  

  public function subscribe($events) {
    $events->listen(
      'App\Events\Process\AggregatorMonthly',
      'App\Listeners\AggregatorEventListener@aggregateMonthly'
    );

    $events->listen(
      'App\Events\Process\AggregatorDaily',
      'App\Listeners\AggregatorEventListener@aggregateDaily'
    );

    $events->listen(
      'App\Events\Process\RankMonthlyProduct',
      'App\Listeners\AggregatorEventListener@rankMonthlyProduct'
    );
  }

  
}


