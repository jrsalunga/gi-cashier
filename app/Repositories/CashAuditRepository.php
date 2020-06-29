<?php namespace App\Repositories;

use DB;
use Carbon\Carbon;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;

class CashAuditRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['id'];

  public function model() {
    return 'App\\Models\\CashAudit';
  }

  public function associateAttributes($r) {
    $row = [];

    $vfpdate = c(trim($r['TRANDATE']).' 00:00:00');

    $row = [
      'date'      => $vfpdate->format('Y-m-d'),
      'csh_fwdd'  => trim($r['CSH_FWDD']),
      'chk_fwdd'  => trim($r['CHK_FWDD']),
      'csh_disb'  => trim($r['CSH_DISB']),
      'chk_disb'  => trim($r['CHK_DISB']),
      'ca'        => trim($r['CA']),
      'csh_sale'  => trim($r['CSH_SALE']),
      'csh_coll'  => trim($r['CSH_COLL']),
      'chg_sale'  => trim($r['CHG_SALE']),
      'sig_sale'  => trim($r['SIG_SALE']),
      'sig_salep' => trim($r['SIG_SALEP']),
      'sig_saleu' => trim($r['SIG_SALEU']),
      'col_card'  => trim($r['COL_CARD']),
      'col_cardk' => trim($r['COL_CARDK']),
      'col_bdo'   => trim($r['COL_BDO']),
      'col_bdok'  => trim($r['COL_BDOK']),
      'col_din'   => trim($r['COL_DIN']),
      'col_dink'  => trim($r['COL_DINK']),
      'col_food'  => trim($r['COL_FOOD']),
      'col_foodk' => trim($r['COL_FOODK']),
      'col_foodc' => trim($r['COL_FOODC']),
      'col_ca'    => trim($r['COL_CA']),
      'col_cak'   => trim($r['COL_CAK']),
      'col_cas'   => trim($r['COL_CAS']),
      'col_othr'  => trim($r['COL_OTHR']),
      'col_oths'  => trim($r['COL_OTHS']),
      'col_oth2'  => trim($r['COL_OTH2']),
      'col_oth2s' => trim($r['COL_OTH2S']),
      'col_othrk' => trim($r['COL_OTHRK']),
      'col_oth2k' => trim($r['COL_OTH2K']),
      'tot_coll'  => trim($r['TOT_COLL']),
      'tot_collk' => trim($r['TOT_COLLK']),
      'csh_out'   => trim($r['CSH_OUT']),
      'csh_outk'  => trim($r['CSH_OUTK']),
      'deposit'   => trim($r['DEPOSIT']),
      'depositk'  => trim($r['DEPOSITK']),
      'tot_out'   => trim($r['TOT_OUT']),
      'tot_outk'  => trim($r['TOT_OUTK']),
      'tot_disb'  => trim($r['TOT_DISB']),
      'tot_disbk' => trim($r['TOT_DISBK']),
      'csh_bal'   => trim($r['CSH_BAL']),
      'chk_bal'   => trim($r['CHK_BAL']),
      'comp_bal'  => trim($r['COMP_BAL']),
      'csh_cnt'   => trim($r['CSH_CNT']),
      'chk_cnt'   => trim($r['CHK_CNT']),
      'shrt_ovr'  => trim($r['SHRT_OVR']),
      'shrt_cumm' => trim($r['SHRT_CUMM']),
      'p1000_pcs' => trim($r['P1000_PCS']),
      'p1000_amt' => trim($r['P1000_AMT']),
      'p500_pcs'  => trim($r['P500_PCS']),
      'p500_amt'  => trim($r['P500_AMT']),
      'p200_pcs'  => trim($r['P200_PCS']),
      'p200_amt'  => trim($r['P200_AMT']),
      'p100_pcs'  => trim($r['P100_PCS']),
      'p100_amt'  => trim($r['P100_AMT']),
      'p50_pcs'   => trim($r['P50_PCS']),
      'p50_amt'   => trim($r['P50_AMT']),
      'p20_pcs'   => trim($r['P20_PCS']),
      'p20_amt'   => trim($r['P20_AMT']),
      'p10_pcs'   => trim($r['P10_PCS']),
      'p10_amt'   => trim($r['P10_AMT']),
      'p5_pcs'    => trim($r['P5_PCS']),
      'p5_amt'    => trim($r['P5_AMT']),
      'p1_pcs'    => trim($r['P1_PCS']),
      'p1_amt'    => trim($r['P1_AMT']),
      'coins'     => trim($r['COINS']),
      'forex'     => trim($r['FOREX']),
      'checks'    => trim($r['CHECKS']),
      'checks_pcs'=> trim($r['CHECKS_PCS']),
      'tip'       => trim($r['TIP']),
      'crew_kit'  => trim($r['CREW_KIT']),
      'crew_din'  => trim($r['CREW_DIN']),
      'man_cost'   => trim($r['MAN_COST']),     
      'cust_cnt'  => trim($r['CUST_CNT']),
      'tran_cnt'  => trim($r['TRAN_CNT']),
      'tot_disc'  => trim($r['TOT_DISC']),
      'tot_canc'  => trim($r['TOT_CANC']),
      'man_hrs'   => trim($r['MAN_HRS']),
      'man_pay'   => trim($r['MAN_PAY']),
    ];

    return $row;
  }

  public function aggregateByDr($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(\DB::raw('LAST_DAY(date) AS date, sum(csh_sale) as csh_sale, sum(chg_sale) as chg_sale, sum(sig_sale) as sig_sale, sum(col_card) as col_card, sum(col_cardk) as col_cardk, sum(col_ca) as col_ca, sum(col_cak) as col_cak, sum(col_othr) as col_othr, sum(tot_coll) as tot_coll, sum(tot_collk) as tot_collk, sum(csh_disb) as csh_disb, sum(chk_disb) as chk_disb, sum(deposit) as deposit, sum(depositk) as depositk,
sum(tot_out) as tot_out, sum(tot_outk) as tot_outk, sum(comp_bal) as comp_bal, sum(csh_cnt) as csh_cnt, sum(checks) as checks, sum(forex) as forex, sum(shrt_ovr) as shrt_ovr, sum(shrt_ovr) as shrt_cumm,
sum(checks_pcs) as checks_pcs, sum(csh_out) as csh_out, sum(csh_outk) as csh_outk, sum(col_bdok) as col_bdok, sum(col_dink) as col_dink')) //count(id) as change_item
                ->whereBetween('date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('branch_id', $branchid);
    })->skipCache()->first();
  }
}