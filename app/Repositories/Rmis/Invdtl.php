<?php namespace App\Repositories\Rmis;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class Invdtl extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['id'];

  public function model() {
    return 'App\\Models\\Rmis\\Invdtl';
  }

  public function whereDate(Carbon $date) {
  	 return $this->scopeQuery(function($query) use ($date) {
      return $query->where('a.date', $date->format('Y-m-d'))
      							->where('a.posted', 1)
  									->where('a.cancelled', 0)
  									->where('i.cancelled', 0)
                    ->where('b.posted', 1)
                    ->where('b.cancelled', 0)
  									->from('invdtl as i')
                    ->leftJoin('invhdr as a', 'a.id', '=', 'i.invhdrid')
                    ->leftJoin('orderhdr as b', 'a.id', '=', 'b.invhdrid')
                    ->select(DB::raw('i.*, (a.vtotal+a.xtotal+a.ztotal) as subtotal, a.refno, a.date, a.timestop, a.tableno, a.tablename, a.pax, a.saletype, a.scpax, a.scdisc, a.pwddisc, a.vatamount, a.svcamount, a.uidcreate, a.vtotal, a.discountid, a.discamount, b.refno as ordrefno, a.timestart as ordtime'))
                    ->groupBy('i.id')
                    ->orderBy('a.refno')
                    ->orderBy('i.lineno');
    });
  }

  
	

}