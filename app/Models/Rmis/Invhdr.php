<?php namespace App\Models\Rmis;

use App\Models\BaseModel;

class Invhdr extends BaseModel {

	protected $connection = 'rmis';
  protected $table = 'invhdr';
	protected $guarded = ['id'];
  protected $dates = ['date'];
	protected $appends = ['vatxmpt'];



	public function invdtls() {
    return $this->hasMany('App\Models\Rmis\Invdtl', 'invhdrid');
  }

  public function scinfos() {
    return $this->hasMany('App\Models\Rmis\Scinfo', 'invhdrid');
  }	

   public function pwdinfos() {
    return $this->hasMany('App\Models\Rmis\Pwdinfo', 'invhdrid');
  } 

  public function terminal() {
    return $this->belongsTo('App\Models\Rmis\Terminal', 'terminalid');
  }

  public function orderhdrs() {
    return $this->hasMany('App\Models\Rmis\Orderhdr', 'invhdrid');
  }

  public function orpaydtls() {
    return $this->hasMany('App\Models\Rmis\Orpaydtl', 'invhdrid');
  }

  public function getVatxmptAttribute(){
    if ($this->pax>0)
      return (($this->vtotal - $this->ctotal) * (($this->scpax+$this->pwdpax)/$this->pax)) - $this->vatxsales;
    return 0;
  }

  public function srefno() {
    return substr($this->refno,4);
  }
  
}
