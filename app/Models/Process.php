<?php namespace App\Models;

use App\Models\BaseModel;

class Process extends BaseModel {

	protected $table = 'for_process';
  public $timestamps = false;
  //protected $appends = ['date'];
  protected $dates = ['filedate'];
  //protected $fillable = ['branchid', 'size', 'terminal', 'filename', 'remarks', 'userid', 'year', 'month', 'mimetype'];
  protected $guarded = ['id'];
  protected $casts = [
    'process' => 'boolean',
  ];


  
}
