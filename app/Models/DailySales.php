<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class DailySales extends BaseModel {

	//protected $connection = 'boss';
	protected $table = 'dailysales';
	public $timestamps = false;
 	protected $fillable = ['date', 'branchid', 'managerid', 'sales', 'cos', 'tips', 'custcount', 'crew_din', 'crew_kit', 'empcount', 
        'mancost', 'headspend', 'tipspct', 'mancostpct', 'cospct', 'purchcost', 'salesemp', 'slsmtd_totgrs', 
        'chrg_total', 'chrg_csh', 'chrg_chrg', 'chrg_othr', 'bank_totchrg', 'disc_totamt', 'opened_at', 'closed_at', 'trans_cnt', 'man_hrs', 'man_pay', 'depo_cash', 'depo_check', 'sale_csh', 'sale_chg', 'sale_sig'];
	//protected $guarded = ['id'];
	protected $casts = [
    'sales' => 'float',
    'cos' => 'float',
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
  ];


	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  public function getDateAttribute($value){
    return Carbon::parse($value.' 00:00:00');
  }

  public function getOpex() {
    if(Carbon::parse($this->date->format('Y-m-d'))->lt(Carbon::parse('2016-01-01')))
      return 0;
    else
      return $this->purchcost - $this->cos;
  }

  public function get_opexpct($format=true) {
    if ($this->sales>0) {
      if ($format)
        return number_format(($this->getOpex()/$this->sales)*100, 2);
      else
        return ($this->getOpex()/$this->sales)*100;
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