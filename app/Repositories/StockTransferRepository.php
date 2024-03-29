<?php namespace App\Repositories;

use DB;
use Exception;
use Carbon\Carbon;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use App\Repositories\SupplierRepository as SupplierRepo;
use App\Repositories\ComponentRepository as CompRepo;

class StockTransferRepository extends BaseRepository implements CacheableInterface
//class StockTransferRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;

  protected $order = ['date', 'descriptor'];
  public $supplier;
  private $component;

  public function __construct(SupplierRepo $supplierrepo, CompRepo $comprepo) {
    parent::__construct(app());
    $this->supplier   = $supplierrepo;
    $this->component  = $comprepo;
  }
  
  public function model() {
    return 'App\\Models\\StockTransfer';
  }

  public function verifyAndCreate($data) {

    $component = $this->component->verifyAndCreate(array_only($data, ['comp', 'ucost', 'unit', 'supno', 'catname']));
    $supplier = $this->supplier->verifyAndCreate(array_only($data, ['supno', 'supname', 'branchid', 'tin', 'terms']));


    $expensecode = 'XUD';
    $expenseid = 'XUD';
    if ($component->compcat->expense) {
      $expensecode = $component->compcat->expense->code;
      $expenseid = $component->compcat->expense->id;
    }


    $attr = [
      'date' => $data['date'],
      'componentid' => $component->id,
      'qty' => $data['qty'],
      'uom' => $data['unit'],
      'ucost' => $data['ucost'],
      'tcost' => $data['tcost'],
      'terms' => $data['terms'],
      'supprefno' => $data['supprefno'],
      'vat' => $data['vat'],
      'to' => $data['to'],
      'supplierid' => $supplier->id,
      'branchid' => $data['branchid'],
      'expensecode' => $expensecode,
      'expenseid' => $expenseid,
    ];

    try {
      $this->create($attr);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
  }


  public function associateAttributes($r) {
    $row = [];

    $vfpdate = c(trim($r['PODATE']).' 00:00:00');

    $row = [
      'date'      => $vfpdate->format('Y-m-d'),
      'comp'      => trim($r['COMP']),
      'unit'      => trim($r['UNIT']),
      'qty'       => trim($r['QTY']),
      'ucost'     => trim($r['UCOST']),
      'tcost'     => trim($r['TCOST']),
      'supno'     => trim($r['SUPNO']),
      'supname'   => trim($r['SUPNAME']),
      'catname'   => trim($r['CATNAME']),
      'vat'       => trim($r['VAT']),
      'terms'     => trim($r['TERMS']),
      'supprefno' => trim($r['FILLER1']),
      'tin'       => trim($r['SUPTIN'])
    ];

    return $row;
  }


  public function getCos($branchid, Carbon $date, array $expcode) {
    return $this->scopeQuery(function($query) use ($branchid, $date, $expcode) {
      return $query->where('stocktransfer.date', $date->format('Y-m-d'))
                    ->where('stocktransfer.branchid', $branchid)
                    ->whereIn('expense.code', $expcode)
                    ->leftJoin('component', 'component.id', '=', 'stocktransfer.componentid')
                    ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                    ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                    ->select(DB::raw('sum(stocktransfer.tcost) as tcost'));
    });
  }

  public function getSumCosByDr($branchid, Carbon $fr, Carbon $to, array $expcode) {
    return $this->scopeQuery(function($query) use ($branchid, $fr, $to, $expcode) {
      return $query->whereBetween('stocktransfer.date', [$fr->format('Y-m-d'), $to->format('Y-m-d')])
                    ->where('stocktransfer.branchid', $branchid)
                    ->whereIn('expense.code', $expcode)
                    ->leftJoin('component', 'component.id', '=', 'stocktransfer.componentid')
                    ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                    ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                    ->select(DB::raw('sum(stocktransfer.tcost) as tcost'));
    });
  }

  public function aggExpByDr(Carbon $fr, Carbon $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('stocktransfer.date as date, compcat.expenseid as expense_id, sum(stocktransfer.qty) as qty, sum(stocktransfer.tcost) as tcost, count(stocktransfer.id) as trans'))
                ->leftJoin('component', 'component.id', '=', 'stocktransfer.componentid')
                ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                ->whereBetween('stocktransfer.date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('stocktransfer.branchid', $branchid)
                ->groupBy('compcat.expenseid');
    })->skipCache()->all();
  }


  public function aggregateComponentByDr($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw("LAST_DAY(date) as date, sum(qty) as qty, sum(tcost) as tcost, componentid as component_id, uom"))
                ->whereBetween('date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('branchid', $branchid)
                ->groupBy('componentid')
                ->groupBy('uom');
    })->skipCache()->all();
  }


	

}