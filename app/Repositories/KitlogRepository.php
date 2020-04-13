<?php namespace App\Repositories;

use DB;
use Carbon\Carbon;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use App\Repositories\ProductRepository as Product;
use App\Models\Kitlog;

class KitlogRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['id'];
  private $product;

  public function model() {
    return 'App\\Models\\Kitlog';
  }

  public function __construct(Product $product) {
    parent::__construct(app());
    $this->product = $product;
  }

  public function verifyAndCreate(array $attributes, $create = true) {

    if ($create)
      $product = $this->product->verifyAndCreate(array_only($attributes, ['product', 'productcode', 'prodcat', 'menucat']));
    else
      $product = $this->product->findByField('descriptor', $attributes['product'], ['code', 'descriptor', 'id'])->first();

    // product = ['id'=>'11EA7D951C1B0D85A7E00911249AB5']
    $product_id = is_null($product) ? '11EA7D951C1B0D85A7E00911249AB5' : $product->id;

    $attr = [
      'date'      => $attributes['date'],
      'ordtime'   => $attributes['ordtime'],
      'served'    => $attributes['served'],
      'qty'       => $attributes['qty'],
      'time'      => $attributes['time'],
      'minute'    => $attributes['minute'],
      'area'      => $attributes['area'],
      'iscombo'   => $attributes['iscombo'],
      'product_id'=> $product_id,
      'branch_id' => $attributes['branch_id'],
    ];

    try {
      if ($create)
        $this->create($attr);
      else {
        $attr['id'] = Kitlog::get_uid();
        Kitlog::insert($attr);
      }
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
  }

  public function associateAttributes($r) {
    $row = [];

    $ordtime  = trim($r['ORDTIME']);
    $served   = substr(trim($r['TERMS']), 1, 8);

    $vfpdate = c(trim($r['ORDDATE']).' 00:00:00');

    $f = in_array(substr($ordtime,0,2)+0, [0,1,2,3,4,5,6]) 
      ? c($vfpdate->copy()->addDay()->format('Y-m-d').' '.$ordtime)
      : c($vfpdate->format('Y-m-d').' '.$ordtime);

    $t = in_array(substr($served,0,2)+0, [0,1,2,3,4,5,6]) 
      ? c($vfpdate->copy()->addDay()->format('Y-m-d').' '.$served)
      : c($vfpdate->format('Y-m-d').' '.$served);

    /*
    conversion
    
    00:14:57 == 14.95

    .95 * 60 = 57

    */


    $row = [
      'date'      => $vfpdate->format('Y-m-d'),
      'ordtime'   => $ordtime,
      'served'    => $served,
      //'time'      => trim($r['COMP2']),
      'time'      => str_pad(($f->diffInHours($t) % 24), 2, "0", STR_PAD_LEFT).':'.str_pad(($f->diffInMinutes($t) % 60), 2, "0", STR_PAD_LEFT).':'.str_pad(($f->diffInSeconds($t) % 60), 2, "0", STR_PAD_LEFT),
      'minute'    => $f->diffInSeconds($t)/60,
      'area'      => trim($r['COMPUNIT4']),
      'qty'       => trim($r['QTY']),
      'iscombo'   => empty(trim($r['COMP3'])) ? 0:1,
      'product'   => trim($r['PRODNAME']),
      'productcode'=> trim($r['PRODNO']),
      'ordno'     => trim($r['ORDNO']),
      'menucat'   => trim($r['COMPUNIT2']).trim($r['COMPUNIT3']),
      'prodcat'   => trim($r['CATNAME']),
    ];

    return $row;
  }

  public function aggregateFoodBranchByDr(Carbon $fr, Carbon $to, $branchid) {

    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('product_id, sum(qty) as qty, round(sum(minute)/sum(qty),2) as ave, iscombo, area, max(minute) as max, min(minute) as min'))
                ->whereBetween('date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('branch_id', $branchid)
                ->groupBy('product_id')
                ->groupBy('iscombo')
                ->orderBy('ave', 'desc');
    })->skipCache()->all();
  }

  public function aggregateAreaBranchByDr(Carbon $fr, Carbon $to, $branchid) {

    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(DB::raw('sum(qty) as qty, round(sum(minute)/sum(qty),2) as ave, area, max(minute) as max, min(minute) as min'))
                ->whereBetween('date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('branch_id', $branchid)
                ->groupBy('area')
                ->orderBy('ave', 'desc');
    })->skipCache()->all();
  }
}