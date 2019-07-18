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

    $fr = $this->product->findWhere(['code'=>$data['fr_code'], ['uprice','>','0']], ['uprice','id'])->first();
    $to = $this->product->findWhere(['code'=>$data['to_code'], ['uprice','>','0']], ['uprice','id'])->first();
    
    $attr = [
      'date'          => $data['date'],
      'cslipno'       => $data['cslipno'],
      'group'         => $data['group'],
      'branch_id'     => $data['branch_id'],
      'fr_product_id' => $fr->id,
      'fr_qty'        => $data['fr_qty'],
      'fr_price'      => $fr->uprice,
      'to_product_id' => $to->id,
      'to_qty'        => $data['to_qty'],
      'to_price'      => $to->uprice,
      'diff'          => $to->uprice - $fr->uprice
    ];

    try {
      return $this->create($attr);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }    
  }
}