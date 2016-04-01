<?php namespace App\Repositories;

use App\Models\Purchase;
use App\Repositories\Criterias\ByBranch;

use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Container\Container as App;

class PurchaseRepository extends BaseRepository 
{

	public function __construct(App $app, ByBranch $byBranch) {
      parent::__construct($app);

      $this->pushCriteria($byBranch);
  }


	public function model() {
    return 'App\Models\Purchase';
  }



  public function deleteWhere(array $where){

  	return $this->model->where($where)->delete();
  }


}