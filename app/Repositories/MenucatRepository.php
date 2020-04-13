<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;

class MenucatRepository extends BaseRepository implements CacheableInterface
//class MenucatRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;

  protected $order = ['descriptor'];
  
  public function model() {
    return 'App\\Models\\Menucat';
  }

  public function verifyAndCreate($data) {

    /* NOTE: this product should be on PRODUCT table for error-trapping/validation
    $product = [
      'code'        => 'XUN',
      'descriptor'  => 'UNKNOWN - NOT ON DATABASE',
      'menucat_id   => app()->environment('local') ? '11E7509A985A1C1B0D85A7E0C073910B' : 'A197E8FFBC7F11E6856EC3CDBB4216A7';,
      'prodcat_id   => app()->environment('local') ? '625E2E18BDF211E6978200FF18C615EC' : 'E841F22BBC3711E6856EC3CDBB4216A7',
      'id'          =>'11EA7D951C1B0D85A7E00911249AB5'
    ];
    */
    
    $attr = [
      //'code' => $data['productcode'],
      'descriptor' => $data['menucat'],
    ];

    try {
  	  //$this->create($attr);
  	  return $this->findOrNew($attr, ['descriptor']);
    } catch(Exception $e) {
      throw new Exception('menucat: '.$e->getMessage());
    }
  }


  
	

}