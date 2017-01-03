<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class FileUpload extends BaseModel {


	protected $table = 'file_upload';
	//protected $appends = ['date'];
  protected $dates = ['uploaddate'];
 	protected $fillable = ['branch_id', 'filename', 'size', 'mimetype', 'filetype_id', 'year', 'month', 
      'uploaddate', 'processed','cashier' ,'user_remarks', 'system_remarks', 'terminal', 'user_id'];
	protected $guarded = ['id'];
	protected $casts = [
    'size' => 'float',
    'year' => 'integer',
    'month' => 'integer',
    'processed' => 'boolean',
  ];

	public function branch() {
    return $this->belongsTo('App\Models\Branch');
  }

  
 

 
	
  
}