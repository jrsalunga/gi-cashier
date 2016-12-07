<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\MenucatRepository as MenucatRepo;
use App\Repositories\ProdcatRepository as ProdcatRepo;
use App\Traits\Repository as RepoTrait;

class ProductRepository extends BaseRepository implements CacheableInterface
//class ProductRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;

  protected $menucat;
	protected $prodcat;
	protected $order = ['descriptor'];

  public function __construct(MenucatRepo $menucat, ProdcatRepo $prodcat) {
    parent::__construct(app());
    $this->menucat = $menucat;
		$this->prodcat = $prodcat;
  }


  public function model() {
    return 'App\\Models\\Product';
  }

  public function verifyAndCreate($data) {

    $prodcat = $this->prodcat->verifyAndCreate(array_only($data, ['prodcat']));
    $menucat = $this->menucat->verifyAndCreate(array_only($data, ['menucat']));
    
    $attr = [
      'code' 				=> $data['productcode'],
      'descriptor'  => $data['product'],
      'prodcat_id' 	=> $prodcat->id,
      'menucat_id' 	=> $menucat->id
    ];

    try {
  	  //$this->create($attr);
  	  return $this->findOrNew($attr, ['code', 'descriptor']);
    } catch(Exception $e) {
     	throw new Exception('product: '.$e->getMessage());
    }
  }

  

}