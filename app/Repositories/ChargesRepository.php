<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;

class ChargesRepository extends BaseRepository implements CacheableInterface
//class ChargesRepository extends BaseRepository 
{
  use CacheableRepository, RepoTrait;

  protected $order = ['orddate', 'ordtime', 'recno'];
  
  public function model() {
    return 'App\\Models\\Charges';
  }

  public function associateAttributes($r) {
		$row = [];

		$vfpdate = c(trim($r['ORDDATE']).' '.trim($r['ORDTIME']));
    
	 	if (($r['SR_TCUST']==$r['SR_BODY']) && ($r['SR_DISC']>0)) // 4 4 78.7
      $cuscount = $r['SR_TCUST']; 
    else if ($r['SR_TCUST']>0 && $r['SR_BODY']>0 && $r['SR_DISC']>0)
      $cuscount = 0;
    else
      $cuscount = ($r['SR_TCUST'] + $r['SR_BODY']);
    
    $disc_type = NULL;
    $disc_amt = NULL;
    $a = ['DIS_GPC', 'DIS_VIP', 'DIS_PWD', 'DIS_EMP', 'DIS_SR', 'DIS_UDISC', 'DIS_PROM', 'DIS_G', 'DIS_H', 'DIS_I', 'DIS_J', 'DIS_K', 'DIS_L', 'DIS_VX'];
    foreach ($a as $key => $value) {
    	if (isset($r[$value]) && $r[$value]>0) {
    		$disc_type = explode('_', $value)[1];
    		$disc_amt = $r[$value];
    	} 
    }

		$row['cslipno'] 			= trim($r['CSLIPNO']);
		$row['orddate'] 			= $vfpdate->format('Y-m-d');
		$row['ordtime'] 			= $vfpdate->format('H:i:s');
		$row['tblno'] 				= trim($r['CUSNO']);
		$row['chrg_type'] 		= trim($r['CUSNAME']);
		$row['chrg_pct'] 			= trim($r['CHARGPCT']);
		$row['chrg_grs'] 			= trim($r['GRSCHRG']);
		$row['sr_tcust'] 			= trim($r['SR_TCUST']);
		$row['sr_body'] 			= trim($r['SR_BODY']);
		$row['custcount'] 		= trim($cuscount);
		$row['sr_disc'] 			= trim($r['SR_DISC']);
		$row['vat'] 					= trim($r['VAT']);
		$row['bank_chrg'] 		= trim($r['BANKCHARG']);
		$row['tot_chrg'] 			= trim($r['TOTCHRG']);
		$row['balance'] 			= trim($r['BALANCE']);
		$row['terms'] 				= trim($r['TERMS']);
		$row['card_type'] 		= trim($r['CARDTYP']);
		$row['card_no'] 			= trim($r['CARDNO']);
		$row['card_name'] 		= trim($r['CUSADDR1']);
		$row['card_addr'] 		= trim($r['CUSADDR2']);
		$row['tcash'] 				= trim($r['TCASH']);
		$row['tcharge'] 			= trim($r['TCHARGE']);
		$row['tsigned'] 			= trim($r['TSIGNED']);
		$row['vat_xmpt'] 			= trim($r['VAT_XMPT']);
		$row['disc_type'] 		= trim($disc_type);
		$row['disc_amt'] 			= trim($disc_amt);
		$row['remarks'] 			= trim($r['CUSCONT']);
		$row['cashier'] 			= trim($r['REMARKS']);

		return $row;
	}

  


  
	

}