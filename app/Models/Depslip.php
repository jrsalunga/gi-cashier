<?php namespace App\Models;
use Event;
use Carbon\Carbon;
use App\Models\BaseModel;

class Depslip extends BaseModel {

  const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	
	protected $table = 'depslip';
  protected $appends = ['deposit_date'];
  protected $dates = ['date', 'created_at', 'updated_at'];
 	protected $fillable = ['branch_id', 'filename', 'date', 'type', 'time', 'amount', 'file_upload_id', 'cashier',
                'remarks', 'user_id', 'verified', 'matched', 'created_at', 'updated_at'];
	protected $guarded = ['id'];
	protected $casts = [
    'amount' => 'float',
    'verified' => 'boolean',
    'matched' => 'boolean',
  ];

  public static function boot() {

    parent::boot();
    /*
    static::created(function($depslp) {
        Event::fire('depslp.created', $depslp);
    });
    static::updated(function($depslp) {
        Event::fire('depslp.updated', $depslp);
    });
    */

    static::deleted(function($depslp) {
        Event::fire('depslp.deleted', $depslp);
    });
  }

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

  public function isDeletable() {
    return ($this->matched || $this->verified)
      ? false
      : true;
  }

  
 

 
	
  
}