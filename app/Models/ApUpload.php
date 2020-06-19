<?php namespace App\Models;

use App\Models\BaseModel;

class ApUpload extends BaseModel {

	protected $table = 'apupload';
	public $timestamps = true;
	protected $guarded = ['id'];
  protected $dates = ['date', 'created_at', 'updated_at'];

  public function branch() {
    return $this->belongsTo('App\Models\Branch');
  }

  public function supplier() {
    return $this->belongsTo('App\Models\Supplier');
  }

  public function fileUpload() {
    return $this->belongsTo('App\Models\FileUpload');
  }

  public function doctype() {
    return $this->belongsTo('App\Models\Doctype');
  }

  public function user() {
    return $this->belongsTo('App\User');
  }

  public function file_exists() {
    return file_exists($this->file_path());
  }

  public function isDeletable() {
    return ($this->matched || $this->verified || $this->paid)
      ? false
      : true;
  }

  public function file_path() {
    return config('gi-dtr.upload_path.files.'.app()->environment()).'APU'.DS.$this->date->format('Y').DS.session('user.branchcode').DS.$this->date->format('m').DS.$this->filename;
  }
}