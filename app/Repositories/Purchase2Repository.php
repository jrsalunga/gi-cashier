<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Repositories\SupplierRepository as SupplierRepo;
use App\Repositories\ComponentRepository as CompRepo;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Repositories\CompledgerRepository as CompledgerRepo;


//class Purchase2Repository extends BaseRepository implements CacheableInterface
class Purchase2Repository extends BaseRepository 
{
  //use CacheableRepository;
  private $supplier;
  private $component;

	public function __construct(SupplierRepo $supplierrepo, CompRepo $comprepo, CompledgerRepo $ledger) {
    parent::__construct(app());
    $this->supplier   = $supplierrepo;
    $this->component  = $comprepo;
    $this->ledger     = $ledger;
  }

	public function model() {
    return 'App\Models\Purchase2';
  }

  // verify-create component and supplier if not found 
  public function verifyAndCreate($data) {

    $component = $this->component->verifyAndCreate(array_only($data, ['comp', 'ucost', 'unit', 'supno', 'catname']));
    $supplier = $this->supplier->verifyAndCreate(array_only($data, ['supno', 'supname', 'branchid']));
    $attr = [
      'date' => $data['date'],
      'componentid' => $component->id,
      'qty' => $data['qty'],
      //'unit' => $data['unit'],
      'ucost' => $data['ucost'],
      'tcost' => $data['tcost'],
      'terms' => $data['terms'],
      'vat' => $data['vat'],
      'supplierid' => $supplier->id,
      'branchid' => $data['branchid']
    ];

    try {
  	  $this->create($attr);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
    
    /*
     must create the compledger table
    try {
      $this->ledger->findOrNew([
        'purchdate' => $attr['date'],
        'componentid' => $attr['componentid'],
        'cost' => $attr['ucost'],
        'supplierid' => $attr['supplierid'],
        'branchid' => $attr['branchid']], 
        ['componentid', 'cost', 'branchid']);
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
    */
    
  	//test_log($data['date'].' | '.$data['catname']);
  }

  public function deleteWhere(array $where){
  	return $this->model->where($where)->delete();
  }



  

}