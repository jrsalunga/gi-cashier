<?php namespace App\Repositories\Rmis;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class Orpaydtl extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['refno'];

  public function model() {
    return 'App\\Models\\Rmis\\Orpaydtl';
  }


  public function whereDate(Carbon $date) {
  	 return $this->scopeQuery(function($query) use ($date) {
      return $query->where('a.date', $date->format('Y-m-d'))
      							->where('a.posted', 1)
  									->where('a.cancelled', 0)
  									->where('orpaydtl.cancelled', 0)
                    ->leftJoin('invhdr as a', 'a.id', '=', 'orpaydtl.invhdrid')
                    ->select(DB::raw('count(orpaydtl.id) as ismulti, sum(orpaydtl.amount) as amounts, orpaydtl.*, (a.vtotal+a.xtotal+a.ztotal) as subtotal, a.refno as invrefno, a.date, a.timestop, a.tableno, a.tablename, a.pax, a.saletype, a.scpax, a.pwdpax, a.scdisc, a.pwddisc, a.vatamount, a.svcamount, a.uidcreate, a.vtotal, a.discountid, a.discamount, a.totpayline, a.terminalid, a.totpaid, a.totchange, (a.scdisc2 + a.pwddisc2) as promoamt, ((a.vtotal-a.ctotal) * ((a.scpax+a.pwdpax)/a.pax)) - a.vatxsales as vatxmpt'))
                    ->groupBy('orpaydtl.paytype')
                    ->groupBy('a.date')
                    ->groupBy('a.refno')
                    ->orderBy('a.refno');
    });
  }

  public function posted() {
  	return $this->scopeQuery(function($query) {
  		return $query->where('a.posted', 0)
  									->where('a.cancelled', 0)
  									->where('orpaydtl.cancelled', 0)
                    ->leftJoin('invhdr as a', 'a.id', '=', 'orpaydtl.invhdrid');
  	});
  }

  
  
	

}