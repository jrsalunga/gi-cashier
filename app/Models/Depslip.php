<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class Depslip extends BaseModel {

	const CREATED_AT = 'created_at';
	
	protected $table = 'depslip';
  protected $appends = ['deposit_date'];
  protected $dates = ['date', 'created_at'];
 	protected $fillable = ['branch_id', 'filename', 'date', 'time', 'amount', 'file_upload_id', 'cashier' ,'remarks', 'user_id', 'created_at'];
	protected $guarded = ['id'];
	protected $casts = [
    'amount' => 'float',
  ];

	public function branch() {
    return $this->belongsTo('App\Models\Branch');
  }

  public function fileUpload() {
    return $this->belongsTo('App\Models\FileUpload');
  }

  public function user() {
    return $this->belongsTo('App\User');
  }


  public function getDepositDateAttribute(){
    return Carbon::parse($this->date->format('Y-m-d').' '.$this->time);
  }

  
 

 
	
  
}