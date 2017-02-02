<?php namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\SalesmtdRepository as SalesmtdRepo;
use App\Repositories\ProductRepository as ProductRepo;


class SalesmtdController extends Controller 
{
	protected $salesmtd;
	protected $product;


	public function __construct(SalesmtdRepo $salesmtd, ProductRepo $product){

		$this->salesmtd = $salesmtd;
		$this->product = $product;
	
		
	}



	public function create(array $attributes) {

		$product = $this->product->verifyAndCreate(array_only($attributes, ['product', 'productcode', 'prodcat', 'menucat']));
		$attributes['product_id'] = $product->id;

		try {
  	  $this->salesmtd->create($attributes);
    } catch(Exception $e) {
      throw new Exception('salesmtd: '.$e->getMessage());
    }

	}


	public function associateAttributes($r) {
		$row = [];

		$cut = c(trim($r['ORDDATE']).' 06:00:00');
		$vfpdate = c(trim($r['ORDDATE']).' '.trim($r['ORDTIME']));
		$cuscount = substr(trim($r['CUSNO']), 0, strspn(trim($r['CUSNO']), '0123456789'));

		$row['tblno'] 				= trim($r['TBLNO']);
		$row['wtrno'] 				= trim($r['WTRNO']);
		$row['ordno'] 				= trim($r['ORDNO']);
		$row['productcode'] 	= trim($r['PRODNO']);
		$row['product'] 			= trim($r['PRODNAME']);
		$row['qty'] 					= trim($r['QTY']);
		
		$row['uprice'] 				= trim($r['UPRICE']);
		$row['grsamt'] 				= trim($r['GRSAMT']);
		$row['disc'] 					= trim($r['DISC']);
		$row['netamt'] 				= trim($r['NETAMT']);
		$row['prodcat'] 			= trim($r['CATNAME']);
		$row['orddate'] 			= $vfpdate->format('Y-m-d');
		//$row['ordtime'] 			= $vfpdate->format('H:i:s');
		$row['ordtime'] 			= $cut->gt($vfpdate) ? $vfpdate->addDay()->format('Y-m-d H:i:s') : $vfpdate->format('Y-m-d H:i:s');
		$row['recno'] 				= trim($r['RECORD']);
		$row['cslipno'] 			= trim($r['CSLIPNO']);
		if($cuscount < 300) 
			$row['custcount'] 	= $cuscount;
		else
			$row['custcount'] 	= 0;
		$row['paxloc']				= substr(trim($r['CUSNO']), -2);
		$row['group'] 				= trim($r['COMP2']);
		$row['group_cnt'] 		= trim($r['COMP3']);
		$row['remarks'] 			= trim($r['COMP1']);
		$row['cashier'] 			= trim($r['CUSNAME']);
		$row['menucat'] 			= trim($r['COMPUNIT2']).trim($r['COMPUNIT3']);

		return $row;
	}

	public function deleteWhere(array $where) {
		return $this->salesmtd->deleteWhere($where);
	}



	public function test(Request $request) {
		return $this->salesmtd->skipCache()->order()->all(['orddate', 'ordtime', 'recno']);
	}
}


