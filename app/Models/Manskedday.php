<?php namespace App\Models;

use App\Models\BaseModel;
use Carbon\Carbon;

class Manskedday extends BaseModel {

	protected $table = 'manskedday';
	public $incrementing = false;
	public $timestamps = false;
  protected $dates = ['date'];
 	protected $fillable = ['manskedid', 'date', 'custcount', 'headspend', 'empcount', 'workhrs', 'breakhrs', 'loading'];
 	//public static $header = ['code', 'descriptor'];
  protected $casts = [
    'custcount' => 'integer',
    'empcount' => 'integer',
    'headspend' => 'float',
    'workhrs' => 'float',
    'breakhrs' => 'float',
    'overload' => 'float',
    'underload' => 'float'
  ];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    if (app()->environment()==='production')
      $this->setConnection('mysql');
    else  
      $this->setConnection('tk-live');
  }

 	/***************** relations *****************************************************/
	public function manskedhdr() {
    return $this->belongsTo('App\Models\Manskedhdr', 'manskedid');
  }

  public function manskeddtls() {
    return $this->hasMany('App\Models\Manskeddtl', 'mandayid');
  }

  // http://softonsofa.com/tweaking-eloquent-relations-how-to-get-hasmany-relation-count-efficiently/#comment-2063367593
  public function countMandtls(){
    //return $this->manskeddtls()
    return $this->hasOne('App\Models\Manskeddtl', 'mandayid')
      ->selectRaw('mandayid, count(*) as count')
      ->groupBy('mandayid');
  }

  /***************** mutators *****************************************************/
  public function getDateAttribute($value){
      return Carbon::parse($value);
  }

  public function getMandtlsCountAttribute() {
    // if relation is not loaded already, let's do it first
    //if ( ! array_key_exists('countMandtls', $this->relations)) 
    if($this->relationLoaded('countMandtls'))
      $this->load('countMandtls');
   
    $related = $this->getRelation('countMandtls');
   
    // then return the count directly
    return ($related) ? (int) $related->count : 0;
  }

  /***************** over ride base model ******************************************/
  public function next($branchid=null){
    
    $res = $this->query()
      ->select('manskedday.id')
      ->join('manskedhdr', function($join){
                            $join->on('manskedday.manskedid', '=', 'manskedhdr.id')
                                ->where('manskedhdr.branchid', '=', session('user.branchid'));
                            })
      ->where('manskedday.date', '>', $this->date)
      ->orderBy('manskedday.date', 'ASC')->get()->first();

    return $res==null ? 'false':$res;
  }

  public function previous($branchid=null){
    
    $res = $this->query()
      ->select('manskedday.id')
      ->join('manskedhdr', function($join){
                            $join->on('manskedday.manskedid', '=', 'manskedhdr.id')
                                ->where('manskedhdr.branchid', '=', session('user.branchid'));
                            })
      ->where('manskedday.date', '<', $this->date)
      ->orderBy('manskedday.date', 'DESC')->get()->first();

    return $res==null ? 'false':$res;
  }

  


  /***************** misc func *****************************************************/
  public function custCount(){
    if($this->custcount=='0' || $this->custcount=='0.00' || empty($this->custcount))
      return '-';
    else 
      return number_format($this->custcount, 0);
  }

  public function headSpend(){
    if($this->headspend=='0' || $this->headspend=='0.00' || empty($this->headspend))
      return '-';
    else 
      return '&#8369; '. number_format($this->headspend, 2);
  }

  public function workHrs(){
    if($this->workhrs=='0' || $this->workhrs=='0.00' || empty($this->workhrs))
      return '-';
    else 
      return number_format($this->workhrs, 2) + 0;
  }

  public function empCount(){
    if($this->empcount=='0' || $this->empcount=='0.00' || empty($this->empcount))
      return '-';
    else 
      return number_format($this->empcount, 2) + 0;
  }

  public function loadings(){
    if($this->overload=='0' || $this->overload=='0.00' || empty($this->overload))
      $o = '-';
    else {
      $o = number_format($this->overload, 2) + 0;
      $o = '+'.$o.'</span>';
    }
      

    if($this->underload=='0' || $this->underload=='0.00' || empty($this->underload))
      $u = '-';
    else 
      $u = number_format($this->underload, 2) + 0;

    return '<span style="color:blue">'.$o.'</span> / <span style="color:red;">'.$u.'</span>';
  }

 

  public function computeMancost($branch_mancost=0, $formated=false){
    if(($this->custcount*$this->headspend) != 0){
      if($formated)
        return number_format((($this->empcount*$branch_mancost)/($this->custcount*$this->headspend)*100),2).' %'; 
      else
        return ($this->empcount*$branch_mancost)/($this->custcount*$this->headspend)*100;
    } else {
      if($formated)
        return '-';
      else
        return 0;
    }
  }

  public function computeHourcost($branch_mancost=0, $formated=false){
    if(($this->custcount*$this->headspend) != 0){
      if($formated)
        return number_format((($this->workhrs*($branch_mancost/8))/($this->custcount*$this->headspend)*100),2).' %'; 
      else
        return ($this->workhrs*($branch_mancost/8))/($this->custcount*$this->headspend)*100;
    } else {
      if($formated)
        return '-';
      else
        return 0;
    }
  }
}
