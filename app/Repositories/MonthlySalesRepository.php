<?php namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class MonthlySalesRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['date'];

  public function model() {
    return 'App\\Models\\MonthlySales';
  }


  public function rank($date=null) {

    if ($date instanceof Carbon)
      return $this->generateRank($date);
    elseif (is_iso_date($date))
      return $this->generateRank(c($date));
    else
      return $this->generateRank(c());
  }

  private function generateRank(Carbon $date) {

    // return $this->all();
    
    $ms = $this
            ->scopeQuery(function($query) use ($date) {
              return $query->where(DB::raw('MONTH(date)'), $date->format('m'))
                          ->where(DB::raw('YEAR(date)'), $date->format('Y'))
                          ->orderBy('sales', 'DESC');
            })
            ->findWhere([['branch_id','<>', 'ALL']]);
            // ->all();

    if (count($ms)<=0)
      return false;
    
    foreach ($ms as $key => $m) {
      //$this->update(['rank'=>($key+1)], $m->id);
      $m->rank = ($key+1);
      if ($m->sales>0)
        $m->save();

      if ($m->sales<=0 && ($m->date->format('Y-m-d')==$m->date->copy()->lastOfMonth()->format('Y-m-d')))
        $m->delete();
    }
    return true;
    
  }


  public function computeAllMonthlysalesTotal(Carbon $date) {

    $sql = 'LAST_DAY(date) AS date, sum(sales) as sales, sum(sale_csh) as sale_csh, sum(sale_chg) as sale_chg, sum(sale_sig) as sale_sig, sum(cos) as cos, ';
    $sql .= 'sum(food_sales) as food_sales, ((sum(cos)-sum(transcos))/sum(food_sales))*100 as fc, sum(tips) as tips, sum(custcount) as custcount, ';
    $sql .= 'sum(target_cust) as target_cust, sum(crew_din) as crew_din, sum(crew_kit) as crew_kit, sum(empcount) as empcount, sum(target_empcount) as target_empcount, ';
    $sql .= 'sum(mancost) as mancost, (sum(sales)/sum(custcount)) as headspend, (sum(sales)/sum(target_cust)) as target_headspend, sum(mancost) as mancost, ';
    $sql .= '((SUM(tips)/SUM(sales))*100) AS tipspct, ((SUM(mancost)/SUM(sales))*100) AS mancostpct, (SUM(target_mancostpct)/COUNT(id)) AS target_mancostpct, ';
    $sql .= 'SUM(cos) AS cos, ((SUM(cos)/SUM(sales))*100) AS cospct, SUM(purchcost) AS purchcost, (SUM(sales)/SUM(empcount)) AS salesemp, ';
    $sql .= 'SUM(slsmtd_totgrs) AS slsmtd_totgrs, SUM(chrg_total) AS chrg_total, SUM(chrg_csh) AS chrg_csh, SUM(chrg_chrg) AS chrg_chrg, SUM(chrg_othr) AS chrg_othr, ';
    $sql .= 'SUM(shrt_ovr) as shrt_ovr, SUM(grab) AS grab, SUM(grabc) AS grabc, SUM(panda) AS panda, SUM(totdeliver) AS totdeliver, ';
    $sql .= 'SUM(grab_fee) AS grab_fee, SUM(grabc_fee) AS grabc_fee, SUM(panda_fee) AS panda_fee, SUM(totdeliver_fee) AS totdeliver_fee, ';
    $sql .= 'SUM(bank_totchrg) AS bank_totchrg, SUM(disc_totamt) AS disc_totamt, SUM(trans_cnt) AS trans_cnt, SUM(man_hrs) AS man_hrs, SUM(man_pay) AS man_pay, ';
    $sql .= 'SUM(depo_cash) AS depo_cash, SUM(depo_check) AS depo_check, SUM(depslpc) AS depslpc, SUM(depslpk) AS depslpk, SUM(setslp) AS setslp, ';
    $sql .= 'SUM(transcost) AS transcost, SUM(transncos) AS transncos, SUM(transcos) AS transcos, SUM(emp_meal) AS emp_meal, SUM(opex) AS opex, ';
    $sql .= 'SUM(kitlog) AS kitlog, SUM(change_item) AS change_item, SUM(change_item_diff) AS change_item_diff, ';

    $sql .= 'SUM(grab_fee) AS grab_fee, SUM(grabc_fee) AS grabc_fee, SUM(panda_fee) AS panda_fee, SUM(totdeliver_fee) AS totdeliver_fee, ';
    $sql .= 'AVG(sales) as ave_sales, AVG(sale_csh) as ave_sale_csh, AVG(sale_chg) as ave_sale_chg, ';
    $sql .= 'AVG(totdeliver) as ave_deliver,  (SUM(totdeliver)/SUM(sales) * 100) as pct_deliver, ';
    $sql .= 'SUM(tot_dine) as tot_dine, SUM(tot_togo) as tot_togo, AVG(tot_dine) as ave_dine, AVG(tot_togo) as ave_togo, ';
    $sql .= 'SUM(tot_onlrid) as tot_onlrid, SUM(tot_osaletype) as tot_osaletype, AVG(tot_onlrid) as ave_onlrid, AVG(tot_osaletype) as ave_osaletype, ';
    $sql .= 'SUM(zap) AS zap, SUM(zap_fee) AS zap_fee, SUM(zap_delfee) AS zap_delfee, SUM(utang) as utang, SUM(profit_direct) as profit_direct, SUM(vat_xmpt) AS vat_xmpt';
    

    $res = $this->skipCache()
        ->skipCriteria()
        ->scopeQuery(function($query) use ($date, $sql) {
          return $query->select(DB::raw($sql))
            ->where('date', $date->format('Y-m-d'))
            ->where('branch_id', '<>', 'all')
            ->where('sales', '>', 0);
        })
        ->all();

    if (count($res)<0)
      return NULL;
    else
      $r = $res->first();


    return $r;
  }

  
  
	

}