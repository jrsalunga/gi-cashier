<?php namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class MonthProductRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['date'];

  public function model() {
    return 'App\\Models\\MonthProduct';
  }

  public function rank($date=null, $branchid) {
    if ($date instanceof Carbon)
      return $this->generateRank($date, $branchid);
    elseif (is_iso_date($date))
      return $this->generateRank(c($date), $branchid);
    else
      return $this->generateRank(c(), $branchid);
  }

  private function generateRank(Carbon $date, $branchid) {
    $prodcats = $this->getUsedProdcat($date, $branchid);
    foreach ($prodcats as $prodcat) {
   		$p = $this->productByProdcat($date, $branchid, $prodcat->prodcat_id);
	   	if (count($p)<=0)
	    	return false;
	    
		  foreach ($p as $key => $o) {
		  	$o->rank = ($key+1);
		  	test_log($o->qty.' '.($key+1));
	      $o->save();
	    }  
	  }  
  }

  public function getUsedProdcat(Carbon $date, $branchid) {
  	return $this->scopeQuery(function($query) use ($date, $branchid) {
      return $query
      					->select('product.prodcat_id')
      					->leftJoin('product', 'product.id', '=', 'month_product.product_id')
                ->where(DB::raw('MONTH(month_product.date)'), $date->format('m'))
		            ->where(DB::raw('YEAR (month_product.date)'), $date->format('Y'))
		            ->where('month_product.branch_id', $branchid)
		            ->where('month_product.netamt', '>', 0)
                ->groupBy('product.prodcat_id');
    })->skipCache()->all();
  }

  public function productByProdcat(Carbon $date, $branchid, $prodcatid) {
  	return $this->scopeQuery(function($query) use ($date, $branchid, $prodcatid) {
      return $query
      					->select(DB::raw('month_product.qty, month_product.netamt, month_product.rank, month_product.id'))
      					->leftJoin('product', 'product.id', '=', 'month_product.product_id')
                ->where(DB::raw('MONTH(month_product.date)'), $date->format('m'))
		            ->where(DB::raw('YEAR (month_product.date)'), $date->format('Y'))
		            ->where('product.prodcat_id', $prodcatid)
		            ->where('month_product.branch_id', $branchid)
		            ->where('month_product.qty', '>', 0)
		            ->orderBy('month_product.qty', 'desc')
		            ->orderBy('month_product.netamt', 'desc');
    })->skipCache()->all();

  }


  

  
  
	

}