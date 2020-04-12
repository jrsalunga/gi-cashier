<?php namespace App\Models;

use App\Models\BaseModel;

class Purchase2 extends BaseModel {

	protected $table = 'purchase';
  public $timestamps = false;
  //protected $appends = ['date'];
  protected $dates = ['date'];
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

  /*
  public function save(array $options = array()) {

    if (app()->environment('local')) {

      $table = $this->getTable();
      $yr = $this->date->format('Y');

      $this->setTable($table.$yr);
      //test_log($this->getTable());
    }

    parent::save($options);
  }

  public function delete() {

    if (app()->environment('local')) {

      $table = $this->getTable();
      $yr = $this->date->format('Y');

      $this->setTable($table.$yr);
      test_log('delete-'.$this->getTable());
    }

    parent::delete();
  }

  public function update(array $options = array()) {

    if (app()->environment('local')) {

      $table = $this->getTable();
      $yr = $this->date->format('Y');

      $this->setTable($table.$yr);
      //test_log($this->getTable());
    }

    parent::update($options);
  }

  public static function destroy($options) {

    if (app()->environment('local')) {

      $table = $this->getTable();
      $yr = $this->date->format('Y');

      $this->setTable($table.$yr);
      test_log('destroy-'.$this->getTable());
    }

    parent::destroy($options);
  }
  */
  
}
