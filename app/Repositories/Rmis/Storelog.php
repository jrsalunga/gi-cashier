<?php namespace App\Repositories\Rmis;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class Storelog extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['id'];

  public function model() {
    return 'App\\Models\\Rmis\\Storelog';
  }

  public function getSalesfileData(Carbon $date) {
    return $this->scopeQuery(function($query) use ($date) { 
      return $query->where('bizdate', $date->format('Y-m-d'))
                   ->where('terminalid', 'STORE')
                   ->select(DB::raw('terminalid, bizdate, vatsales, vatxsales, vatamount, scdisc, pwddisc, totsales, (vatsales+vatxsales+vatamount) as mall_sales, (scdisc+pwddisc) as totdisc, ((vatsales+vatxsales)-(scdisc+pwddisc)) as mall_net, timeclose, userclose'));
    });
  }

}