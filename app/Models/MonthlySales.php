<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class MonthlySales extends BaseModel {

	//protected $connection = 'boss';
	protected $table = 'monthlysales';
	public $timestamps = false;
  
 	protected $fillable = ['date', 'branch_id', 'sales', 'cos', 'food_sales', 'fc', 'tips', 'custcount', 'crew_din', 'crew_kit', 'empcount', 
        'mancost', 'headspend', 'tipspct', 'mancostpct', 'cospct', 'purchcost', 'salesemp', 'slsmtd_totgrs', 
        'chrg_total', 'chrg_csh', 'chrg_chrg', 'chrg_othr', 'bank_totchrg', 'disc_totamt', 'trans_cnt', 'man_hrs', 'man_pay', 'depo_cash', 'depo_check', 'sale_csh', 'sale_chg', 'sale_sig','transcost', 'transcos', 'transncos', 'opex', 'record_count', 'depslpk', 'depslpc', 'setslp', 'pct_deliver', 'ave_deliver'];
	
  //protected $guarded = ['id'];
  protected $dates = ['date', 'ending_csh_date'];
	protected $casts = [
    'sales' => 'float',
    'cos' => 'float',
    'fc' => 'float',
    'food_sales' => 'float',
    'tips' => 'float',
    'crew_din' => 'integer',
    'crew_kit' => 'integer',
    'custcount' => 'integer',
    'empcount' => 'integer',
    'headspend' => 'float',
    'tipspct' => 'float',
    'mancostpct' => 'float',
    'cospct' => 'float',
    'purchcost' => 'float',
    'salesemp' => 'float',
    'slsmtd_totgrs' => 'float',
    'chrg_total' => 'float',
    'chrg_csh' => 'float',
    'chrg_chrg' => 'float',
    'chrg_othr' => 'float',
    'bank_totchrg' => 'float',
    'disc_totamt' => 'float',
    'depo_cash' => 'float',
    'depo_check' => 'float',
    'sale_csh' => 'float',
    'sale_chg' => 'float',
    'sale_sig' => 'float',
    'transcost' => 'float',
    'transcos' => 'float',
    'transncos' => 'float',
    'opex' => 'float',
    'depslpk' => 'float',
    'depslpc' => 'float',
    'setslp' => 'float',
  ];


	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  

  public function getBeerPurch() {
    if(Carbon::parse($this->date->format('Y-m-d'))->lt(Carbon::parse('2016-01-01')))
      return 0;
    else
      return $this->purchcost - ($this->cos + $this->opex);
  }

  public function getOpex() {
    if(Carbon::parse($this->date->format('Y-m-d'))->lt(Carbon::parse('2016-01-01')))
      return 0;
    else
      return $this->opex + $this->totdeliver_fee + $this->emp_meal;
  }

  public function get_opexpct($format=true) {
    if ($this->sales>0) {
      if ($format)
        return number_format(($this->opex/$this->sales)*100, 2);
      else
        return ($this->opex/$this->sales)*100;
    }
    return 0;
  }

  public function get_cospct($format=true) {
    if ($this->sales>0) {
      if ($format)
        return number_format(($this->cos/$this->sales)*100, 2);
      else
        return ($this->cos/$this->sales)*100;
    }
    return 0;
  }

  public function get_mancostpct($format=true) {
    if ($this->sales>0){
      if ($format)
        return number_format(($this->mancost/$this->sales)*100, 2);
      else
        return ($this>-mancost/$this->sales)*100;
    }
    return 0;
  }

  public function get_tipspct($format=true) {
    if ($this->sales>0){
      if ($format)
        return number_format(($this->tips/$this->sales)*100, 2);
      else
        return ($this->tips/$this->sales)*100;
    }
    return 0;
  }

  public function get_purchcostpct($format=true) {
    if ($this->sales>0){
      if ($format)
        return number_format(($this->purchcost/$this->sales)*100, 2);
      else
        return ($this->purchcost/$this->sales)*100;
    }
    return 0;
  }

  public function get_receipt_ave($format=true) {
    if ($this->trans_cnt>0){
      if ($format)
        return number_format($this->sales/$this->trans_cnt, 2);
      else
        return $this->sales/$this->trans_cnt;
    }
    return 0;
  }

	
	
 

 
	
  
}