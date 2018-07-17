<?php namespace App\Repositories;

use DB;
use Carbon\Carbon;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\Criterias\ByBranch;


class DailySales2Repository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository;

	public function __construct() {
    parent::__construct(app());

     
  }

  public function boot(){
    $this->pushCriteria(new ByBranch(request()));
  }

	public function model() {
    return 'App\Models\DailySales';
  }

  public function findOrNew($attributes, $field) {

    $attr_idx = [];

    if (is_array($field)) {
      foreach ($field as $value) {
        $attr_idx[$value] = array_get($attributes, $value);
      }
    } else {
      $attr_idx[$field] = array_get($attributes, $field);
    }

    $obj = $this->findWhere($attr_idx)->first();

    return !is_null($obj) ? $obj : $this->create($attributes);
  }

  public function firstOrNewField($attributes, $field) {
    	
  	$attr_idx = [];
  	
  	if (is_array($field)) {
  		foreach ($field as $value) {
  			$attr_idx[$value] = array_pull($attributes, $value);
  		}
  	} else {
  		$attr_idx[$field] = array_pull($attributes, $field);
  	}

  	$m = $this->model();
  	// Retrieve by the attributes, or instantiate a new instance...
  	$model = $m::firstOrNew($attr_idx);
  	//$this->model->firstOrNew($attr_idx);
		
  	foreach ($attributes as $key => $value) {
  		$model->{$key} = $value;
  	}

  	return $model->save() ? $model : false;

  }



  public function computeMonthTotal(Carbon $date, $branchid) {

    //return throwException('throw error');

    $sql = 'LAST_DAY(date) AS date, MONTH(date) AS month, YEAR(date) as year, branchid as branch_id, COUNT(id) as record_count, ';
    $sql .= 'SUM(sales) AS sales, SUM(sale_csh) AS sale_csh, SUM(sale_chg) AS sale_chg, SUM(sale_sig) AS sale_sig, ';
    $sql .= 'SUM(custcount) AS custcount, SUM(target_cust) AS target_cust, SUM(crew_din) AS crew_din,  SUM(crew_kit) AS crew_kit,  ';
    $sql .= 'SUM(empcount) AS empcount, SUM(target_empcount) AS target_empcount, SUM(mancost) AS mancost, SUM(headspend) AS headspend, ';
    $sql .= 'SUM(target_headspend) AS target_headspend, ((SUM(tips)/SUM(sales))*100) AS tipspct, ((SUM(mancost)/SUM(sales))*100) AS mancostpct, ';
    //$sql .= '((SUM(target_mancost)/SUM(sales))*100) AS target_mancostpct, ';
    $sql .= 'SUM(cos) AS cos, ((SUM(cos)/SUM(sales))*100) AS cospct, SUM(purchcost) AS purchcost, (SUM(sales)/SUM(empcount)) AS salesemp, ';
    $sql .= 'SUM(slsmtd_totgrs) AS slsmtd_totgrs, SUM(chrg_total) AS chrg_total, SUM(chrg_csh) AS chrg_csh, SUM(chrg_chrg) AS chrg_chrg, ';
    $sql .= 'SUM(chrg_othr) AS chrg_othr, SUM(bank_totchrg) AS bank_totchrg, SUM(disc_totamt) AS disc_totamt, SUM(trans_cnt) AS trans_cnt, ';
    $sql .= 'SUM(man_hrs) AS man_hrs, SUM(man_pay) AS man_pay, SUM(depo_cash) AS depo_cash, SUM(depo_check) AS depo_check, ';
    $sql .= 'SUM(tips) AS tips, ';
    $sql .= 'SUM(opex) AS opex, SUM(transcost) AS transcost, SUM(transcos) AS transcos';
    

    $res = $this->skipCache()
        ->skipCriteria()
        ->scopeQuery(function($query) use ($date, $branchid, $sql) {
          return $query->select(DB::raw($sql))
            ->where(DB::raw('MONTH(date)'), $date->format('m'))
            ->where(DB::raw('YEAR (date)'), $date->format('Y'))
            ->where('branchid', $branchid)
            ->where('sales', '>', 0);
        })
        ->all();

    if (count($res)<0)
      return NULL;
    else
      $r = $res->first();


    return is_null($r->branch_id) ? NULL : $r;

  }

}