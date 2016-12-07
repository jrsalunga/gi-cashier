<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;

class ProdcatRepository extends BaseRepository implements CacheableInterface
//class MenucatRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;

  protected $order = ['descriptor'];
  
  public function model() {
    return 'App\\Models\\Prodcat';
  }

  public function verifyAndCreate($data) {
    
    $attr = [
      //'code' => $data['productcode'],
      'descriptor' => $data['prodcat'],
    ];

    try {
  	  //$this->create($attr);
  	  return $this->findOrNew($attr, ['descriptor']);
    } catch(Exception $e) {
      throw new Exception('prodcat: '.$e->getMessage());
    }
  }


	

}