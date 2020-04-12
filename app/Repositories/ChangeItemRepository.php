<?php namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\ProductRepository;
use App\Traits\Repository as RepoTrait;

class ChangeItemRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;
  
  protected $order = ['id'];

  private $component;

  public function __construct(ProductRepository $product) {
    parent::__construct(app());
    $this->product   = $product;
  }

  public function model() {
    return 'App\\Models\\ChangeItem';
  }

  public function verifyAndCreate($data) {

    // $fr = $this->product->findWhere(['code'=>trim($data['fr_code']), ['uprice','>','0']], ['uprice','id'])->first();
    // $to = $this->product->findWhere(['code'=>trim($data['to_code']), ['uprice','>','0']], ['uprice','id'])->first();
    $fr = $this->product->findWhere(['code'=>trim($data['fr_code'])], ['uprice','id'])->first();
    $to = $this->product->findWhere(['code'=>trim($data['to_code'])], ['uprice','id'])->first();

    $fr_uprice = 0;
    if (is_null($fr))
      $fr_id = $data['fr_code'];
    else {
      $fr_id = $fr->id;
      $fr_uprice = $fr->uprice;
    }

    $to_uprice = 0;
    if (is_null($to)) 
      $to_id = $data['to_code'];
    else {
      $to_id = $to->id;
      $to_uprice = $to->uprice;
    }
    
    $attr = [
      'date'          => $data['date'],
      'cslipno'       => $data['cslipno'],
      'group'         => $data['group'],
      'branch_id'     => $data['branch_id'],
      'fr_product_id' => $fr_id,
      'fr_qty'        => $data['fr_qty'],
      'fr_price'      => $fr_uprice,
      'to_product_id' => $to_id,
      'to_qty'        => $data['to_qty'],
      'to_price'      => $to_uprice,
      'diff'          => $to_uprice - $fr_uprice
    ];

    try {
      return $this->create($attr);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }    
  }

  public function aggregateByDr($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query
                ->select(\DB::raw('sum(diff) as change_item_diff, sum(to_qty) as change_item')) //count(id) as change_item
                ->whereBetween('date', 
                  [$fr->format('Y-m-d'), $to->format('Y-m-d')]
                  )
                ->where('branch_id', $branchid);
    })->skipCache()->first();
  }

  public function aggregateGroupiesByDr($fr, $to, $branchid) {
    return $this->scopeQuery(function($query) use ($fr, $to, $branchid) {
      return $query->select(\DB::raw('`group` as code, sum(fr_qty) as change_item, sum(diff) as diff'))
                    ->whereBetween('date', [$fr->format('Y-m-d'), $to->format('Y-m-d')])
                    ->where('branch_id', $branchid)
                    ->groupBy('group')
                    ->orderBy('group');
    })->skipCache()->all();
  }
}