<?php namespace App\Models;
use Event;
use Carbon\Carbon;
use App\Models\BaseModel;

class Setslp extends BaseModel {

  const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	
	protected $table = 'setslp';
  protected $dates = ['date', 'created_at', 'updated_at'];
  protected $appends = ['datetime', 'bizdate'];
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
    
    static::created(function($setslp) {
        Event::fire('setslp.created', $setslp);
    });

    static::updated(function($setslp) {
        Event::fire('setslp.updated', $setslp);
    });

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


  public function file_exists() {
    return file_exists($this->file_path());
  }

  public function file_path() {
    return config('gi-dtr.upload_path.files.'.app()->environment()).'SETSLP'.DS.$this->date->format('Y').DS.session('user.branchcode').DS.$this->date->format('m').DS.$this->filename;
  }

  public function getDatetimeAttribute(){
    if (isset($this->date) && isset($this->time)) 
      return Carbon::parse($this->date->format('Y-m-d').' '.$this->time);
    return NULL;
  }

  public function getBizdateAttribute(){
    if (is_null($this->datetime))
      return NULL;
    $s = Carbon::parse($this->date->format('Y-m-d').' 06:00:00');
    $e = Carbon::parse($this->date->copy()->addDay()->format('Y-m-d').' 05:59:59');
    return $this->datetime->gte($s) && $this->datetime->lte($e)
          ? $this->datetime : $s->subDay();
  }





  
 

 
	
  
}