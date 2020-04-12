<?php namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\SalesmtdRepository as SalesmtdRepo;
use App\Repositories\ProductRepository as ProductRepo;
use App\Repositories\ChangeItemRepository as ChangeItem;

class SalesmtdController extends Controller 
{
	protected $salesmtd;
	protected $product;

	public function __construct(SalesmtdRepo $salesmtd, ProductRepo $product, ChangeItem $changeItem){

		$this->salesmtd = $salesmtd;
		$this->product = $product;
    $this->changeItem = $changeItem;
	}

	public function create(array $attributes) {
		
		$product = $this->product->verifyAndCreate(array_only($attributes, ['product', 'productcode', 'prodcat', 'menucat', 'uprice']));
		$attributes['product_id'] = $product->id;

    /* change item stuff */
    if (!empty($attributes['change_item'])) {
      $len = explode(' ', $attributes['change_item']);

      if (count($len)>1) {
        $k = 0;
        $ci = [];
        $ci['date']       = $attributes['orddate'];
        $ci['cslipno']    = $attributes['cslipno'];
        $ci['branch_id']  = $attributes['branch_id'];
        $ci['group']      = $attributes['group'];

        // update this if there is prince increase
        $last_price_update = '2020-01-01';
        if (Carbon::now()->gte(Carbon::parse($last_price_update))) {
          foreach ($len as $key => $prod) {
            if (!empty($prod)) {
              
              preg_match_all('/(\d+(?:\.\d+)?)([A-Z0-9]+)/m', $attributes['change_item'], $matches, PREG_SET_ORDER, 0);
              
              if ($k==0) {
                $ci['fr_qty']  = $matches[$k][1];
                $ci['fr_code'] = $matches[$k][2];
              }

              if ($k==1) {
                $ci['to_qty']  = $matches[$k][1];
                $ci['to_code'] = $matches[$k][2];
              } 

              $k++;
            }
          } // end: foreach
        } // end: gte

        try {
          $this->changeItem->verifyAndCreate($ci);
        } catch(Exception $e) {
          throw new Exception('Controller::changeItem '.$e->getMessage());
        }
      } // end: if count($len)
    }

    /* end: change item stuff */

		try {
  	  $this->salesmtd->create($attributes);
    } catch(Exception $e) {
      throw new Exception('Controller::create '.$e->getMessage());
    }
	}

	// for PosUploadRepository->updateProductsTable
	public function importProduct(array $attributes) {
		return $this->product->importAndCreate(array_only($attributes, ['product', 'productcode', 'prodcat', 'menucat', 'ucost', 'uprice']));
	}

	public function associateAttributes($r) {
		$row = [];

		$cut = c(trim($r['ORDDATE']).' 06:00:00');
		$t = is_time(trim($r['ORDTIME'])) ? trim($r['ORDTIME']) : '00:00:01';
		$vfpdate = c(trim($r['ORDDATE']).' '.$t);
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
		// $row['ordtime'] 			= $vfpdate->format('H:i:s');
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
    // $row['change_item']   = trim($r['COMP4']);
		$row['remarks'] 			= trim($r['COMP1']);
		$row['cashier'] 			= trim($r['CUSNAME']);
		$row['menucat'] 			= trim($r['COMPUNIT2']).trim($r['COMPUNIT3']);
    $row['change_item']   = trim($r['COMP4']);

		return $row;
	}

	public function deleteWhere(array $where) {
		return $this->salesmtd->deleteWhere($where);
	}

	public function test(Request $request) {
		return $this->salesmtd->skipCache()->order()->all(['orddate', 'ordtime', 'recno']);
	}
}


