<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class DailySales extends BaseModel {

	//protected $connection = 'boss';
	protected $table = 'dailysales';
	public $timestamps = false;
 	protected $fillable = ['date', 'branchid', 'managerid', 'sales', 'cos', 'tips', 'custcount', 'crew_din', 'crew_kit', 'empcount', 
        'mancost', 'headspend', 'tipspct', 'mancostpct', 'cospct', 'purchcost', 'salesemp', 'slsmtd_totgrs', 
        'chrg_total', 'chrg_csh', 'chrg_chrg', 'chrg_othr', 'bank_totchrg', 'disc_totamt', 'opened_at', 'closed_at', 'trans_cnt', 'man_hrs', 'man_pay'];
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
  ];


	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  public function getDateAttribute($value){
    return Carbon::parse($value.' 00:00:00');
  }

	
	
 

 
	
  
}