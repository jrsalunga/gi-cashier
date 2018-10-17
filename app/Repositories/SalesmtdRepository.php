<?php namespace App\Repositories;
use DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;

class SalesmtdRepository extends BaseRepository implements CacheableInterface
//class SalesmtdRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['orddate', 'ordtime', 'recno'];

  public function model() {
    return 'App\\Models\\Salesmtd';
  }

  public function aggregateProductByDr($fr, $to, $branchid) {
  	return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('product_id, sum(qty) as qty, sum(netamt) as netamt, count(id) as trans'))
                ->whereBetween('orddate', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('branch_id', $branchid)
                ->groupBy('product_id')
                ->orderBy('qty', 'desc');
    })->skipCache()->all();
  }

  public function aggregateProdcatByDr($fr, $to, $branchid) {
  	return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('product.prodcat_id, sum(salesmtd.qty) as qty, sum(salesmtd.netamt) as sales, (COUNT(salesmtd.id) - SUM(IF(salesmtd.qty<1, (ABS(salesmtd.qty)*2),0))) as trans'))
                ->leftJoin('product', 'product.id', '=', 'salesmtd.product_id')
                ->whereBetween('salesmtd.orddate', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('salesmtd.branch_id', $branchid)
                ->groupBy('product.prodcat_id')
                ->orderBy('qty', 'desc');
    })->skipCache()->all();
  }

  public function aggregateProdcatByDr2($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw("product.prodcat_id, sum(salesmtd.qty) as qty, sum(salesmtd.netamt) as sales, (COUNT(salesmtd.id) - SUM(IF(salesmtd.qty<1, (ABS(salesmtd.qty)*2),0))) as trans, ((sum(salesmtd.netamt)/(SELECT sum(a.netamt) from salesmtd as a where a.branch_id = '".$branchid."' and a.orddate between '".$fr->format('Y-m-d')."' and '".$to->format('Y-m-d')."'))*100) as pct"))
                ->leftJoin('product', 'product.id', '=', 'salesmtd.product_id')
                ->whereBetween('salesmtd.orddate', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('salesmtd.branch_id', $branchid)
                ->groupBy('product.prodcat_id')
                ->orderBy('qty', 'desc');
    })->skipCache()->all();
  }

  public function aggregateGroupiesByDr($fr, $to, $branchid) {
  	 return DB::table(DB::raw("(select salesmtd.group as code, group_cnt AS qty, SUM(salesmtd.netamt) AS netamt from salesmtd where salesmtd.orddate between '".$fr->format('Y-m-d')."' and '".$to->format('Y-m-d')."'
        and salesmtd.branch_id = '".$branchid."' and salesmtd.group_cnt > 0
        group by salesmtd.group, salesmtd.group_cnt, salesmtd.cslipno) AS a"))
        ->select(DB::raw('a.code as code, SUM(a.qty) as qty, SUM(a.netamt) as netamt, count(a.code) as trans'))
        ->groupBy('code')->get();
  }


  public function aggregateGroupiesByDr2($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query->whereBetween('salesmtd.orddate', [$fr->format('Y-m-d'), $to->format('Y-m-d')])
                    ->where('salesmtd.group', '<>', '')
                    ->leftJoin('product', 'product.id', '=', 'salesmtd.product_id')
                    ->select(DB::raw('salesmtd.group, group_cnt as qty, sum(salesmtd.grsamt) as grsamt, cslipno'))
                    ->groupBy('salesmtd.group')
                    ->groupBy('salesmtd.group_cnt')
                    ->groupBy('salesmtd.cslipno')
                    ->orderBy(DB::raw('salesmtd.group'), 'asc');
    })->skipOrder();
  }
  
	

}