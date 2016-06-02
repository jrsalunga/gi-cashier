<?php namespace App\Models;

use App\Models\BaseModel;

class Compledger extends BaseModel {

	protected $table = 'compledger';
  public $timestamps = false;
  //protected $appends = ['date'];
  protected $dates = ['purchdate'];
  //protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
  protected $guarded = ['id'];
  protected $casts = [
    'cost' => 'float',
  ];

  public function branch() {
    return $this->belongsTo('App\Models\Branch', 'branchid');
  }

	public function supplier() {
    return $this->belongsTo('App\Models\Supplier', 'supplierid');
  }

  public function component() {
    return $this->belongsTo('App\Models\Component', 'componentid');
  }
  
}
