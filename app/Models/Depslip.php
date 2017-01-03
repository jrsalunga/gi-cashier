<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class Depslip extends BaseModel {

	const CREATED_AT = 'created_at';
	
	protected $table = 'depslip';
  protected $dates = ['date'];
 	protected $fillable = ['branch_id', 'filename', 'date', 'amount', 'file_upload_id', 'cashier' ,'remarks', 'user_id', 'created_at'];
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

  
 

 
	
  
}