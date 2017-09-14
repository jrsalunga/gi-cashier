<?php namespace App\Repositories;
use DB;
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
    $supplier = $this->supplier->verifyAndCreate(array_only($data, ['supno', 'supname', 'branchid', 'tin']));
    $attr = [
      'date' => $data['date'],
      'componentid' => $component->id,
      'qty' => $data['qty'],
      //'unit' => $data['unit'],
      'ucost' => $data['ucost'],
      'tcost' => $data['tcost'],
      'terms' => $data['terms'],
      'supprefno' => $data['supprefno'],
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

  public function getFoodCost(Carbon $dr, $branchid) {
    return $this->scopeQuery(function($query) use ($dr, $branchid) {
      return $query->where('purchase.date', $dr->format('Y-m-d'))
                    ->where('purchase.branchid', $branchid)
                    ->where('expense.expscatid', '7208AA3F5CF111E5ADBC00FF59FBB323')
                    ->leftJoin('component', 'component.id', '=', 'purchase.componentid')
                    ->leftJoin('compcat', 'compcat.id', '=', 'component.compcatid')
                    ->leftJoin('expense', 'expense.id', '=', 'compcat.expenseid')
                    ->select(DB::raw('purchase.date, sum(purchase.qty) as qty, sum(purchase.tcost) as tcost'));
    });
  }


  public function associateAttributes($r) {
    $row = [];

    $vfpdate = c(trim($r['PODATE']).' 00:00:00');

    $row = [
      'date'      => $vfpdate->format('Y-m-d'),
      'comp'      => trim($r['COMP']),
      'unit'      => trim($r['UNIT']),
      'qty'       => trim($r['QTY']),
      'ucost'     => trim($r['UCOST']),
      'tcost'     => trim($r['TCOST']),
      'supno'     => trim($r['SUPNO']),
      'supname'   => trim($r['SUPNAME']),
      'catname'   => trim($r['CATNAME']),
      'vat'       => trim($r['VAT']),
      'terms'     => trim($r['TERMS']),
      'supprefno' => trim($r['FILLER1']),
      'tin'       => trim($r['SUPTIN'])
    ];

    return $row;
  }




  

}