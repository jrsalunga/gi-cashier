<?php namespace App\Models;

use App\Models\BaseModel;

class Purchase2 extends BaseModel {

	protected $table = 'purchase';
  public $timestamps = false;
  //protected $appends = ['date'];
  //protected $dates = ['filedate'];
  //protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
  protected $guarded = ['id'];
  protected $casts = [
    'qty' => 'float',
    'ucost' => 'float',
    'tcost' => 'float',
    'vat' => 'float'
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
