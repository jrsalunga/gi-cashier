<?php namespace App\Models;

use App\Models\BaseModel;

class CashAudit extends BaseModel {

	protected $table = 'csh_audt';
	public $timestamps = true;
  protected $dates = ['date', 'created_at', 'updated_at'];
	protected $guarded = ['id'];
	protected $casts = [
    'csh_fwdd' => 'float',
    'chk_fwdd' => 'float',
    'csh_disb' => 'float',
    'chk_disb' => 'float',
    'ca' => 'float',
    'csh_sale' => 'float',
    'csh_coll' => 'float',
    'chg_sale' => 'float',
    'sig_sale' => 'float',
    'sig_salep' => 'float',
    'sig_saleu' => 'float',
    'col_card' => 'float',
    'col_cardk' => 'float',
    'col_bdo' => 'float',
    'col_bdok' => 'float',
    'col_din' => 'float',
    'col_dink' => 'float',
    'col_food' => 'float',
    'col_foodk' => 'float',
    'col_foodc' => 'float',
    'col_ca' => 'float',
    'col_cak' => 'float',
    'col_othr' => 'float',
    'col_oth2' => 'float',
    'col_othrk' => 'float',
    'col_oth2k' => 'float',
    'tot_coll' => 'float',
    'tot_collk' => 'float',
    'csh_out' => 'float',
    'csh_outk' => 'float',
    'deposit' => 'float',
    'depositk' => 'float',
    'tot_out' => 'float',
    'tot_outk' => 'float',
    'tot_disb' => 'float',
    'tot_disbk' => 'float',
    'csh_bal' => 'float',
    'chk_bal' => 'float',
    'comp_bal' => 'float',
    'csh_cnt' => 'float',
    'chk_cnt' => 'float',
    'shrt_ovr' => 'float',
    'shrt_cumm' => 'float',
    'p1000_pcs' => 'integer',
    'p1000_amt' => 'float',
    'p500_pcs' => 'integer',
    'p500_amt' => 'float',
    'p200_pcs' => 'integer',
    'p200_amt' => 'float',
    'p100_pcs' => 'integer',
    'p100_amt' => 'float',
    'p50_pcs' => 'integer',
    'p50_amt' => 'float',
    'p20_pcs' => 'integer',
    'p20_amt' => 'float',
    'p10_pcs' => 'integer',
    'p10_amt' => 'float',
    'p5_pcs' => 'integer',
    'p5_amt' => 'float',
    'p1_pcs' => 'integer',
    'p1_amt' => 'float',
    'coins' => 'float',
    'forex' => 'float',
    'checks' => 'float',
    'checks_pcs' => 'integer',
    'tip' => 'float',
    'crew_kit'  => 'integer',
    'crew_din'  => 'integer',
    'man_cost' => 'float',
    'cust_cnt' => 'integer',
    'tran_cnt' => 'integer',
    'tot_disc' => 'float',
    'tot_canc' => 'float',
    'man_hrs' => 'float',
    'man_pay' => 'float',
  ];

  public function branch() {
    return $this->belongsTo('App\Models\Branch')->select(['code', 'descriptor', 'id']);
  }

  
 

 
	
  
}