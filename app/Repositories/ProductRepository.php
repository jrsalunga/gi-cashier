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

    //$prodcat = $this->prodcat->verifyAndCreate(array_only($data, ['prodcat']));
    //$menucat = $this->menucat->verifyAndCreate(array_only($data, ['menucat']));

    if (empty(trim($data['prodcat'])))
      $prodcatid = app()->environment('local') ? '625E2E18BDF211E6978200FF18C615EC' : 'E841F22BBC3711E6856EC3CDBB4216A7';
    else {
      $prodcat = $this->prodcat->verifyAndCreate(array_only($data, ['prodcat']));
      $prodcatid = $prodcat->id;
    }

    if (empty(trim($data['menucat'])))
      $menucatid = app()->environment('local') ? '11E7509A985A1C1B0D85A7E0C073910B' : 'A197E8FFBC7F11E6856EC3CDBB4216A7';
    else {
      $menucat = $this->menucat->verifyAndCreate(array_only($data, ['menucat']));
      $menucatid = $menucat->id;
    }
    
    $attr = [
      'code'        => $data['productcode'],
      'descriptor'  => $data['product'],
      'prodcat_id'  => $prodcatid,
      'menucat_id'  => $menucatid
    ];

    try {
      //$this->create($attr);
      return $this->findOrNew($attr, ['code', 'descriptor']); //  find or create
      //return $this->firstOrNew($attr, ['code', 'descriptor']); // update when find or create
    } catch(Exception $e) {
      throw new Exception('product: '.$e->getMessage());
    }
  }


  public function importAndCreate($data) {

    if (empty($data['prodcat'])) 
      $prodcatid = app()->environment('local') ? '625E2E18BDF211E6978200FF18C615EC' : 'E841F22BBC3711E6856EC3CDBB4216A7';
    else {
      $prodcat = $this->prodcat->verifyAndCreate(array_only($data, ['prodcat']));
      $prodcatid = $prodcat->id;
    }
    

    if (empty($data['menucat'])) 
      $menucatid = app()->environment('local') ? '62605A33BDF211E6978200FF18C615EC' : 'E84204C8BC3711E6856EC3CDBB4216A7';
    else {
      $menucat = $this->menucat->verifyAndCreate(array_only($data, ['menucat']));
      $menucatid = $menucat->id;
    }
    
    $attr = [
      'code'        => $data['productcode'],
      'descriptor'  => $data['product'],
      'ucost'       => $data['ucost'],
      'uprice'      => $data['uprice'],
      'prodcat_id'  => $prodcatid,
      'menucat_id'  => $menucatid
    ];

    try {
      //return $this->findOrNew($attr, ['code', 'descriptor', 'ucost', 'uprice']);
      return $this->firstOrNewField($attr, ['code', 'descriptor']);
    } catch(Exception $e) {
      throw new Exception('product:import '.$e->getMessage());
    }
  }

  

}