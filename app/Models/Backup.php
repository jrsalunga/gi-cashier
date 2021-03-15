<?php namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;

class Backup extends BaseModel {

	protected $table = 'backup';
	public $timestamps = false;
	protected $appends = ['date'];
  protected $dates = ['filedate', 'uploaddate'];
 	protected $fillable = ['branchid', 'filename', 'filedate', 'size', 'mimetype', 'year', 'month', 
        'uploaddate', 'cashier', 'processed', 'remarks', 'terminal', 'lat', 'long', 'userid'];
	protected $guarded = ['id'];
	protected $casts = [
    'size' => 'float',
    'year' => 'integer',
    'month' => 'integer',
    'lat' => 'float',
    'long' => 'float',
    'processed' => 'integer',
  ];

	public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

  public function getUploaddateAttribute($value){
    return Carbon::parse($value);
  }

  public function getDateAttribute(){
  	return $this->parseDate();
  }

  private function parseDate() {
  	$f = pathinfo($this->filename, PATHINFO_FILENAME);

		$m = substr($f, 2, 2);
		$d = substr($f, 4, 2);
		$y = '20'.substr($f, 6, 2);
		
		if(is_iso_date($y.'-'.$m.'-'.$d))
			return Carbon::parse($y.'-'.$m.'-'.$d);
		else 
			return null;
  }
 
  public function isLocked() {
    return $this->processed >= 10 ? true : false;
  }
}