<?php namespace App\Models;
use Event;
use Carbon\Carbon;
use App\Models\BaseModel;

class Setslp extends BaseModel {

  const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	
	protected $table = 'setslp';
  protected $dates = ['date', 'created_at', 'updated_at'];
 	protected $fillable = ['branch_id', 'filename', 'date', 'terminal_id', 'time', 'amount', 'file_upload_id', 'cashier',
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
    static::created(function($setslp) {
        Event::fire('setslp.created', $setslp);
    });
    static::updated(function($setslp) {
        Event::fire('setslp.updated', $setslp);
    });
    */

    static::deleted(function($setslp) {
        Event::fire('setslp.deleted', $setslp);
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


  public function getTransDate(){
    return Carbon::parse($this->date->format('Y-m-d').' '.$this->time);
  }

  public function isDeletable() {
    return ($this->matched || $this->verified)
      ? false
      : true;
  }

  
 

 
	
  
}