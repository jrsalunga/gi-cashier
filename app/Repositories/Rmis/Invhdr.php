<?php namespace App\Repositories\Rmis;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;
use Carbon\Carbon;
use DB;

class Invhdr extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['id'];

  public function model() {
    return 'App\\Models\\Rmis\\Invhdr';
  }


  public function zreadsales(Carbon $date) {
    return $this->scopeQuery(function($query) use ($date) {
                  return $query->select(DB::raw("bizdate, date, min(refno) as refnobeg, max(refno)as refnoend,
convert(count(*),decimal(6,0)) as txncount, sum(pax) as pax, sum(vtotal) as vtotal,
sum(xtotal) as xtotal, sum(ztotal) as ztotal, sum(ctotal) as ctotal,
sum(discamount) as discamount, sum(svcamount) as svcamount, sum(taxamount) as taxamount,
sum(vatsales) as vatsales, sum(vatxsales) as vatxsales, sum(vatzsales) as vatzsales,
sum(vatamount) as vatamount, sum(scpax) as scpax, sum(scdisc) as scdisc,
sum(pwdpax) as pwdpax, sum(pwddisc) as pwddisc, sum(totsales) as totsales,
sum(totpaid) as totpaid, sum(totchange) as totchange, (select convert(count(*),decimal(6,0)) from invhdr x where x.date='".$date->format('Y-m-d')."'and x.cancelled=1 and x.posted=1) as txncancel"))
                        ->where('date', $date->format('Y-m-d'))
                        ->where('posted', 1)
                        ->where('cancelled', 0)
                        ->groupBy('bizdate');
                });
  }


  public function zreadpay(Carbon $date) {
    return $this->scopeQuery(function($query) use ($date) {
      return $query->select(DB::raw('b.paytype, convert(count(*), decimal(6,0)) as paycount, sum(b.amount) as amount'))
              ->leftJoin('orpaydtl as b', 'invhdr.id', '=', 'b.invhdrid')
              ->where('date', $date->format('Y-m-d'))
              ->where('posted', 1)
              ->where('invhdr.cancelled', 0)
              ->where('b.cancelled', 0)
              ->groupBy('b.paytype');
    });
  }

  public function tzreadsales(Carbon $date, $terminalid) {
    return $this->scopeQuery(function($query) use ($date, $terminalid) {
                  return $query->select(DB::raw("bizdate, date, min(refno) as refnobeg, max(refno)as refnoend,
convert(count(*),decimal(6,0)) as txncount, sum(pax) as pax, sum(vtotal) as vtotal,
sum(xtotal) as xtotal, sum(ztotal) as ztotal, sum(ctotal) as ctotal,
sum(discamount) as discamount, sum(svcamount) as svcamount, sum(taxamount) as taxamount,
sum(vatsales) as vatsales, sum(vatxsales) as vatxsales, sum(vatzsales) as vatzsales,
sum(vatamount) as vatamount, sum(scpax) as scpax, sum(scdisc) as scdisc,
sum(pwdpax) as pwdpax, sum(pwddisc) as pwddisc, sum(totsales) as totsales,
sum(totpaid) as totpaid, sum(totchange) as totchange, (select convert(count(*),decimal(6,0)) from invhdr x where x.date='".$date->format('Y-m-d')."'and x.cancelled=1 and x.posted=1 and x.terminalid='".$terminalid."') as txncancel"))
                        ->where('date', $date->format('Y-m-d'))
                        ->where('posted', 1)
                        ->where('cancelled', 0)
                        ->where('terminalid', $terminalid)
                        ->groupBy('bizdate');
                });
  }

  public function tzreadpay(Carbon $date, $terminalid) {
    return $this->scopeQuery(function($query) use ($date, $terminalid) {
      return $query->select(DB::raw('b.paytype, convert(count(*), decimal(6,0)) as paycount, sum(b.amount) as amount'))
              ->leftJoin('orpaydtl as b', 'invhdr.id', '=', 'b.invhdrid')
              ->where('date', $date->format('Y-m-d'))
              ->where('posted', 1)
              ->where('invhdr.cancelled', 0)
              ->where('b.cancelled', 0)
              ->where('terminalid', $terminalid)
              ->groupBy('b.paytype');
    });
  }

  public function usedTerminal(Carbon $date) {
    
    $u = $this->skipCache()
            ->orderBy('terminalid', 'DESC')
            ->findWhere(['date' => $date->format('Y-m-d')], ['terminalid']);

    return is_null($u)
      ? NULL
      : $u->unique('terminalid')->pluck('terminalid')->all();
  }
 



}