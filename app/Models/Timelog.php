<?php namespace App\Models;

use App\Models\BaseModel;
use Carbon\Carbon;

class Timelog extends BaseModel {


	protected $table = 'timelog';
	public $timestamps = false;
 	//protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
	protected $guarded = ['id'];
		protected $casts = [
    'txncode' => 'integer',
    'entrytype' => 'integer'
  ];

	public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    if (app()->environment()==='production')
      $this->setConnection('mysql-tk');
      
    $this->setConnection('mysql-tk');
  }

	public function employee() {
    return $this->belongsTo('App\Models\Employee', 'employeeid');
  }


  

 


  /***************** misc functions *****************************************************/
  public function getTxnCode(){
  	switch ($this->txncode) {
			case 1:
				return 'Time In';
				break;
			case 2:
				return 'Break Start';
				break;
			case 3:
				return 'Break End';
				break;
			case 4:
				return 'Time Out';
				break;
			default:
				return '-';
				break;
		}
	}


	/***************** mutators *****************************************************/
  public function getDatetimeAttribute($value){
    return Carbon::parse($value);
  }

  public function getCreatedateAttribute($value){
    return Carbon::parse($value);
  }
 

 
	
  
}