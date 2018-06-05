<?php namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class MonthlySalesRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['date'];

  public function model() {
    return 'App\\Models\\MonthlySales';
  }


  public function rank($date=null) {

    if ($date instanceof Carbon)
      return $this->generateRank($date);
    elseif (is_iso_date($date))
      return $this->generateRank(c($date));
    else
      return $this->generateRank(c());
  }

  private function generateRank(Carbon $date) {
    
    $ms = $this->skipCache()
            ->scopeQuery(function($query) use ($date) {
              return $query->where(DB::raw('MONTH(date)'), $date->format('m'))
                          ->where(DB::raw('YEAR(date)'), $date->format('Y'));
            })
            ->orderBy('sales', 'DESC')
            ->all();

    if (count($ms)<=0)
      return false;
    
    foreach ($ms as $key => $m) {
      //$this->update(['rank'=>($key+1)], $m->id);
      $m->rank = ($key+1);
      if ($m->sales>0)
        $m->save();

      if ($m->sales<=0 && ($m->format('Y-m-d')==$m->copy()->firstOfMonth()->format('Y-m-d')))
        $m->delete();
    }
    return true;
    
  }

  
  
	

}