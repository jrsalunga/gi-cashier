<?php namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;
use App\Repositories\ComponentRepository as CompRepo;

class BegBalRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['date'];
  private $component;

  public function __construct(CompRepo $comprepo) {
    parent::__construct(app());
    $this->component  = $comprepo;
  }

  public function model() {
    return 'App\\Models\\BegBal';
  }


  public function verifyAndCreate($data, $saved=false) { // check if may saved purchases or paid na

    if (abs($data['ucost'])==0 && abs($data['qty'])==0 && empty($data['comp'])) {
      // dont create record
    } else {



      $component = $this->component->verifyAndCreate(array_only($data, ['comp', 'ucost', 'unit', 'supno', 'catname']));
      $componentid = is_null($component) ? 'XUD' : $component->id;

      $expensecode = 'XUD';
      $expenseid = 'XUD';
      if ($component->compcat->expense) {
        $expensecode = $component->compcat->expense->code;
        $expenseid = $component->compcat->expense->id;
      }


      $attr = [
        'date' => $data['date'],
        'component_id' => $componentid,
        'uom' => $data['unit'],
        'qty' => $data['qty'],
        'ucost' => $data['ucost'],
        'tcost' => $data['tcost'],
        'branch_id' => $data['branch_id'],
        'expensecode' => $expensecode,
        'expense_id' => $expenseid,
      ];

      try {
        if ($saved)
          $this->findOrNew($attr, ['date', 'componentid', 'qty', 'component_id', 'branch_id', 'tcost']);
        else
          $this->create($attr);
      } catch(Exception $e) {
        throw new Exception($e->getMessage());    
      }
      
    }
  }

  public function associateAttributes($r) {
    $row = [];

    $row = [
      'comp' => trim($r['COMP']),
      'unit'      => trim($r['UNIT']),
      'qty'       => trim($r['QTY']),
      'ucost'     => trim($r['UCOST']),
      'tcost'     => trim($r['TCOST']),
      'catname'   => trim($r['CATNAME']),
      'supno'     => trim($r['SUPNO']),
      'expensecode'=> trim($r['SUPNO']),
      'expense'   => trim($r['NAME'])
    ];

    return $row;
  }


  public function aggregateByDr(Carbon $fr, Carbon $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('LAST_DAY(date) as date, sum(qty) as qty, sum(tcost) as tcost, count(id) as trans'))
                ->whereBetween('date', [$fr->format('Y-m-d'), $to->format('Y-m-d')])
                ->where('branch_id', $branchid);
    })->skipCache()->first();
  }


  public function aggregateExpenseByDr($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw("LAST_DAY(date) as date, expensecode, expense_id, sum(tcost) as tcost"))
                ->whereBetween('date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('branch_id', $branchid)
                ->groupBy('expense_id');
    })->skipCache()->all();
  }


  public function aggregateComponentByDr($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw("LAST_DAY(begbal.date) as date, sum(begbal.qty) as qty, sum(begbal.tcost) as tcost, begbal.component_id as component_id, begbal.expensecode as expensecode, begbal.expense_id as expense_id, component.status as status, component.uom as uom"))
                ->leftJoin('component', 'component.id', '=', 'begbal.component_id')
                ->whereBetween('begbal.date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('begbal.branch_id', $branchid)
                ->groupBy('begbal.component_id')
                ->groupBy('begbal.uom');
    })->skipCache()->all();
  }

  
  public function getCos($branchid, Carbon $date, array $expcode) {
    return $this->scopeQuery(function($query) use ($branchid, $date, $expcode) {
      return $query->where('begbal.date', $date->format('Y-m-d'))
                    ->where('begbal.branch_id', $branchid)
                    ->whereIn('expense.code', $expcode)
                    ->leftJoin('component', 'component.id', '=', 'begbal.component_id')
                    ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                    ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                    ->select(DB::raw('sum(begbal.tcost) as tcost'));
    })->skipCache()->first();
  }


  public function allBegBal(Carbon $date, $branchid) {

    $arr = [];
    $arr['begcost'] = $arr['begcos'] = $arr['begncos'] = 0;

    $cost = $this->aggregateByDr($date, $date, $branchid);
    if (!is_null($cost))
      $arr['begcost'] = $cost->tcost;

    $cos = $this->getCos($branchid, $date, config('gi-config.expensecode.cos'));
    if (!is_null($cos))
      $arr['begcos'] = $cos->tcost;

    $ncos = $this->getCos($branchid, $date, config('gi-config.expensecode.ncos'));
    if (!is_null($ncos))
      $arr['begncos'] = $ncos->tcost;

    return $arr;
  }
  
	

}