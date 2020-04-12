<?php namespace App\Models;

use App\Models\BaseModel;

class EmploymentActivity extends BaseModel {

	protected $table = 'emp_activity';
 	protected $guarded = ['id'];
  public $dates = ['stage1', 'stage2', 'stage3', 'stage4', 'stage5', 'stage6', 'stage7', 'stage8', 'stage9', 'stage10', 'stage11', 'stage12'];
  public $timestamps = true;


 	public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    // if (app()->environment()==='production')
      // $this->setConnection('mysql');
      
    $this->setConnection('mysql');
  }

	public function employee() {
    return $this->belongsTo('App\Models\Employee')->select(['code', 'lastname', 'firstname', 'lastname', 'middlename', 'id']);
  }

  public function branch() {
    return $this->belongsTo('App\Models\Branch')->select(['code', 'descriptor', 'email', 'id']);
  }

  public function branchto() {
    return $this->belongsTo('App\Models\Branch', 'to_branch_id')->select(['code', 'descriptor', 'email', 'id']);
  }
  
}
