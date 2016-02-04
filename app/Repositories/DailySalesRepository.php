<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Models\DailySales;
use Prettus\Repository\Eloquent\BaseRepository;
use App\Repositories\BossBranchRepository as BBRepo;
use App\Repositories\Criterias\BossBranchCriteria;
use App\Repositories\Criterias\BranchDailySalesCriteria;
use Illuminate\Container\Container as Application;

class DailySalesRepository extends BaseRepository {

	
	public $bossbranch;

	public function __construct(Application $app, BBRepo $bbrepo, DailySales $dailysales) {
		parent::__construct($app);
		$this->bossbranch = $bbrepo;
		$this->bossbranch->pushCriteria(new BossBranchCriteria);
	}
	





  /**
   * Specify Model class name
   *
   * @return string
   */
  function model()
  {
      return "App\\Models\\DailySales";
  }



  public function branchByDate(Carbon $date) {
  	$ads = []; // array of dailysales
  	$bb = $this->bossbranch->with([
  			'branch'=>function($q){
  				$q->select(['code', 'descriptor', 'mancost', 'id']);
  			}
  		])->all();

  	foreach ($bb as $b) { // each bossbranch
  		$ds = DailySales::whereBranchid($b->branchid)
  											->where('date', $date->format('Y-m-d'))
  											->first();
  		if(is_null($ds))
	  		$ads[$b->branch->code]['ds'] = NULL;

  		$ads[$b->branch->code]['ds'] = $ds;
  		$ads[$b->branch->code]['br'] = $b->branch;
   	}

   	return array_sort_recursive($ads);
  	return array_sort($ads, function($value){
  		return $value;
  	});
		

  }
}