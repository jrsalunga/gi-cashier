<?php namespace App\Repositories;
use DB;
use Carbon\Carbon;
use App\Repositories\SupplierRepository as SupplierRepo;
use App\Repositories\ComponentRepository as CompRepo;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\CompledgerRepository as CompledgerRepo;
use App\Traits\Repository as RepoTrait;

//class Purchase2Repository extends BaseRepository implements CacheableInterface
class Purchase2Repository extends BaseRepository 
{
  use RepoTrait;
  private $supplier;
  private $component;

	public function __construct(SupplierRepo $supplierrepo, CompRepo $comprepo, CompledgerRepo $ledger) {
    parent::__construct(app());
    $this->supplier   = $supplierrepo;
    $this->component  = $comprepo;
    $this->ledger     = $ledger;
  }

	public function model() {
    return 'App\Models\Purchase2';
  }

  // verify-create component and supplier if not found 
  public function verifyAndCreate($data, $saved=false) { // check if may saved purchases or paid na

    if (abs($data['ucost'])==0 && abs($data['qty'])==0 && empty($data['comp'])) {
      // dont create record
    } else {



      $component = $this->component->verifyAndCreate(array_only($data, ['comp', 'ucost', 'unit', 'supno', 'catname']));
      $componentid = is_null($component) ? 'XUD' : $component->id;
      $supplier = $this->supplier->verifyAndCreate(array_only($data, ['supno', 'supname', 'branchid', 'tin', 'terms']));
      $supplierid = is_null($supplier) ? 'XUD' : $supplier->id;

      $expensecode = 'XUD';
      $expenseid = 'XUD';
      if ($component->compcat->expense) {
        $expensecode = $component->compcat->expense->code;
        $expenseid = $component->compcat->expense->id;
      }


      $utang = 0;
      // $tcost = 0;
      switch ($data['terms']) {
        case 'H':
          $paytype = 4;
          // $tcost = $data['tcost'];
          break;
        case 'U':
          $paytype = 3;
          $utang = $data['tcost'];
          break;
        case 'K':
          $paytype = 2;
          // $tcost = $data['tcost'];
          break;
        case 'C':
          $paytype = 1;
          // $tcost = $data['tcost'];
          break;
        default:
          $paytype = 0;
          // $tcost = $data['tcost'];
          break;
      }

      

      

      $attr = [
        'date' => $data['date'],
        'componentid' => $componentid,
        'qty' => $data['qty'],
        'uom' => $data['unit'],
        'ucost' => $data['ucost'],
        'tcost' => $data['tcost'],
        // 'tcost' => $tcost,
        'utang' => $utang,
        'terms' => $data['terms'],
        'supprefno' => $data['supprefno'],
        'vat' => $data['vat'],
        'supplierid' => $supplierid,
        'branchid' => $data['branchid'],
        'paytype' => $paytype,
        'expensecode' => $expensecode,
        'expenseid' => $expenseid,
      ];

      try {
        if ($saved)
          $this->findOrNew($attr, ['date', 'componentid', 'qty', 'supprefno', 'supplierid', 'branchid', 'tcost']);
        else
          $this->create($attr);
      } catch(Exception $e) {
        throw new Exception($e->getMessage());    
      }
      
    }
    
    /*
     must create the compledger table
    try {
      $this->ledger->findOrNew([
        'purchdate' => $attr['date'],
        'componentid' => $attr['componentid'],
        'cost' => $attr['ucost'],
        'supplierid' => $attr['supplierid'],
        'branchid' => $attr['branchid']], 
        ['componentid', 'cost', 'branchid']);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
    */
    
  	//test_log($data['date'].' | '.$data['catname']);
  }

  public function deleteWhere(array $where){
    /*
    if (app()->environment('local')) {

      $table = $this->model->getTable();
      $yr = Carbon::parse($where['date'])->format('Y');

      $this->model->setTable($table.$yr);
      //test_log($this->getTable());
    }
    */
  	return $this->model->where($where)->delete();
  }

  public function getFoodCost(Carbon $dr, $branchid) {
    return $this->scopeQuery(function($query) use ($dr, $branchid) {
      return $query->where('purchase.date', $dr->format('Y-m-d'))
                    ->where('purchase.terms','<>', 'U')
                    ->where('purchase.branchid', $branchid)
                    ->where('expense.expscatid', '7208AA3F5CF111E5ADBC00FF59FBB323')
                    ->leftJoin('component', 'component.id', '=', 'purchase.componentid')
                    ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                    ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                    ->select(DB::raw('purchase.date, sum(purchase.qty) as qty, sum(purchase.tcost) as tcost'));
    });
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
      return $query->where('purchase.date', $date->format('Y-m-d'))
                    ->where('purchase.branchid', $branchid)
                    ->where('purchase.terms','<>', 'U')
                    ->whereIn('expense.code', $expcode)
                    ->leftJoin('component', 'component.id', '=', 'purchase.componentid')
                    ->leftJoin('supplier', 'supplier.id', '=', 'purchase.supplierid')
                    ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                    ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                    ->select(DB::raw('sum(purchase.tcost) as tcost'));
    });
  } 

  public function getOpex($branchid, Carbon $date, $expscatid='8A1C2FF95CF111E5ADBC00FF59FBB323') {
    return $this->scopeQuery(function($query) use ($branchid, $date, $expscatid) {
      return $query->where('purchase.date', $date->format('Y-m-d'))
                    ->where('purchase.branchid', $branchid)
                    ->where('expense.expscatid', $expscatid)
                    ->where('purchase.terms','<>', 'U')
                    ->leftJoin('component', 'component.id', '=', 'purchase.componentid')
                    ->leftJoin('supplier', 'supplier.id', '=', 'purchase.supplierid')
                    ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                    ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                    ->select(DB::raw('sum(purchase.tcost) as tcost'));
    });
  }

  public function aggCompByDr(Carbon $fr, Carbon $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('purchase.componentid, sum(purchase.qty) as qty, sum(purchase.tcost) as tcost, count(purchase.id) as trans, component.status as status, component.uom as uom'))
                ->leftJoin('component', 'component.id', '=', 'purchase.componentid')
                ->whereBetween('purchase.date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('purchase.branchid', $branchid)
                ->where('purchase.terms','<>', 'U')
                ->groupBy('purchase.componentid')
                ->groupBy('purchase.uom');
    })->all();
  }

  public function aggExpByDr(Carbon $fr, Carbon $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('compcat.expenseid as expense_id, sum(purchase.qty) as qty, sum(purchase.tcost) as tcost, count(purchase.id) as trans, expense.ordinal as ordinal'))
                ->leftJoin('component', 'component.id', '=', 'purchase.componentid')
                ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                ->whereBetween('purchase.date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('purchase.branchid', $branchid)
                ->where('purchase.terms','<>', 'U')
                ->groupBy('compcat.expenseid');
    })->all();
  }


  public function getDailySalesData($date, $branchid) {
    $arr = [
      'date' => $date->format('Y-m-d'),
      'branchid'  => $branchid
    ];

    $c = $this->sumFieldsByDate('tcost', $date, $branchid);
    if (is_null($c))
      $arr['purchcost'] = 0;
    else
      $arr['purchcost'] = $c->tcost;

    $c = $this->getCos($branchid, $date, config('gi-config.expensecode.cos'))->all();
    if (is_null($c->first()->tcost))
      $arr['cos'] = 0;
    else
      $arr['cos'] = $c->first()->tcost;

    $c = $this->getOpex($branchid, $date)->all();
    if (is_null($c->first()->tcost))
      $arr['opex'] = 0;
    else
      $arr['opex'] = $c->first()->tcost;

    return $arr;
  }


  // added 02/12/2024
  // rewrite function
  // para hindi sumama ung Tran Emp Meal sa total purchase sa POS vs Module | artisan backlog:month VS reg upload
  public function sumFieldsByDate($field, Carbon $date, $branchid) {
    
    $select = '';
    $arr = [];
    if (is_array($field)) {
      foreach ($field as $key => $value) {
        $arr[$key] = 'sum('.$value.') as '.$value;
      }
      $select = join(',', $arr);
    } else {
      $select = 'sum('.$field.') as '.$field;
    }

    return $this
        //->skipCache()
        ->scopeQuery(function($query) use ($select, $date, $branchid) {
          return $query->select(DB::raw($select))
            ->whereNotIn('componentid', ['11E8BB3635ABF63DAEF21C1B0D85A7E0'])
            ->where('date', $date->format('Y-m-d'))
            ->where('branchid', $branchid);
        })
        ->first();
  }



  

}