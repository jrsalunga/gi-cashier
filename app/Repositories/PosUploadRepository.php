<?php namespace App\Repositories;

use App\Repositories\Exceptions\RepositoryException;
use File;
use Illuminate\Database\QueryException;
use StdClass;
use Exception;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Backup;
use App\Models\DailySales;
use App\Repositories\Repository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use Illuminate\Support\Facades\Storage;
use App\Repositories\DailySalesRepository;
use App\Repositories\DailySales2Repository as DS2;
use App\Repositories\PurchaseRepository as Purchase;
use App\Repositories\Purchase2Repository as PurchaseRepo;
use App\Http\Controllers\SalesmtdController as SalesmtdCtrl;
use App\Repositories\ChargesRepository as ChargesRepo;
use App\Repositories\StockTransferRepository as TranferRepo;
use App\Repositories\ChangeItemRepository as ChangeItemRepo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use App\Services\KitlogImporter as KitlogImporter;
use App\Services\DbfImporter;
use App\Repositories\CashAuditRepository as CashAudit;

class PosUploadRepository extends Repository
{
    
  public $ds;
  public $extracted_path;
  public $purchase;
  public $purchase2;
  public $charges;
  public $transfer;
  public $changeItem;
  public $sysinfo = null;
  protected $salesmtdCtrl;
  protected $expense_array = [];
  protected $non_cos_array = [];
  public $kitlogImporter;
  public $dbfImporter;
  public $cashAudit;

    

  /**
  * @param App $app
  * @param Collection $collection
  * @throws RepositoryException
  */
  public function __construct(App $app, Collection $collection, DailySalesRepository $dailysales, Purchase $purchase, PurchaseRepo $purchaserepo, SalesmtdCtrl $salesmtdCtrl, ChargesRepo $charges, TranferRepo $transfer, DS2 $ds2, ChangeItemRepo $changeItem, KitlogImporter $kitlogImporter, DbfImporter $dbfImporter, CashAudit $cashAudit) {
    
    parent::__construct($app, $collection);

    $this->ds = $dailysales;
    $this->ds2 = $ds2;
    $this->purchase   = $purchase;
    $this->purchase2  = $purchaserepo;
    $this->salesmtdCtrl = $salesmtdCtrl;
    $this->charges = $charges;
    $this->transfer = $transfer;
    $this->changeItem = $changeItem;
    $this->kitlogImporter = $kitlogImporter;
    $this->dbfImporter = $dbfImporter;
    $this->cashAudit = $cashAudit;

    //$this->get_foodcost();
    $this->expense_array = config('gi-config.expensecode.cos'); // no "DN","DB","DA","CG","IC"
    $this->non_cos_array = config('gi-config.expensecode.ncos');
  }

  private function get_foodcost() {
    $expense = new ExpenseRepository;
    $this->expense_array =  $expense->findWhere(['expscatid'=> '7208AA3F5CF111E5ADBC00FF59FBB323'], ['code'])->pluck('code')->toArray();
  }

  public function model() {
      return 'App\Models\Backup';
  }

  public function extract($src, $pwd=NULL, $d=true, $brcode='ALL'){
   
    $dir = $d ? $this->realFullPath($src) : $src;

    $zip = new ZipArchive();
    $zip_status = $zip->open($dir);

    if($zip_status === true) {

      if(!is_null($pwd))
        $zip->setPassword($pwd);
      
      if (session('user.branchcode'))
        $path = storage_path().DS.'backup'.DS.session('user.branchcode').DS.pathinfo($src, PATHINFO_FILENAME);
      else
        $path = storage_path().DS.'backup'.DS.$brcode.DS.pathinfo($src, PATHINFO_FILENAME);
      
      if(is_dir($path)) {
        $this->removeDir($path);
      }
      mkdir($path, 0777, true);
       
      $this->extracted_path = $path;

      if(!$zip->extractTo($path)) {
        $zip->close();
        return false;
      }

      $zip->close();

      return true;
    } else {
      return false;
    }
  }


  private function getSysinfo($r) {
    $s = new StdClass;
    foreach ($r as $key => $value) {
      $f = strtolower($key);
      $s->{$f} = isset($r[$key]) ? $r[$key] : NULL;
    }
    return $s;
  }


  public function getBackupCode() {
    $dbf_file = $this->extracted_path.DS.'SYSINFO.DBF';
    $data =[];

    if (file_exists($dbf_file)) { 
      $db = dbase_open($dbf_file, 0);
      $row = dbase_get_record_with_names($db, 1);

      $this->sysinfo = $this->getSysinfo($row);

      $code = trim($row['GI_BRCODE']);

      // if (c()->gte(c('2021-11-01'))) { // check if valid EOD Backup
      
        // $eod = trim($row['FILLER']);
        // if (is_null($eod) || $eod == '') {
        //   throw new Exception("Invalid EOD backup. Kindly run the POS EOD process (5-1) before uploading the backup.");
        // } else {
        //   $data['eod'] = c($eod);
        // }

      // }

      dbase_close($db);
      if(empty($code)) {
        throw new Exception("Cannot locate Branch Code on backup");
      }
      else 
        $data['code'] = $code;   

      return $data;
    
    } else {
      return NULL;
      throw new Exception("Cannot locate SYSINFO"); 
    }
    
  }

  public function isEoD($backup) {


    if (c($backup->filedate)->gte(c('2021-11-01'))) { // check if valid EOD Backup
      // throw new Exception($backup->filedate); 

      $dbf_file = $this->extracted_path.DS.'SYSINFO.DBF';
      if (file_exists($dbf_file)) { 
        $db = dbase_open($dbf_file, 0);
        $row = dbase_get_record_with_names($db, 1);

        $eod = trim($row['FILLER']);
        if (is_null($eod) || $eod == '') {
          throw new Exception("Invalid EOD backup. Kindly run the POS EOD process (5-1) before uploading the backup.");
        } else {
          $data['eod'] = c($eod);
        }
        dbase_close($db);
      } else {
        throw new Exception("Cannot locate SYSINFO"); 
      }
    }

    
    $dbf_file = $this->extracted_path.DS.'ORDERS.DBF';
    if (file_exists($dbf_file)) { 
      $db = dbase_open($dbf_file, 0);
      $record_numbers = dbase_numrecords($db);
      $grsamt = 0;

      for ($i = 1; $i <= $record_numbers; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $grsamt += $row['GRSAMT'];
      }
      dbase_close($db);
    
      if ($record_numbers>0 || $grsamt>0)
        throw new Exception("Validation Error: Invalid EoD backup. ".$record_numbers." unsettled item(s) on ORDERS.DBF with total amount of ". number_format($grsamt, 2).". Please upload the correct backup file."); 

    } else {
      throw new Exception("Cannot locate ORDERS.DBF"); 
    }

    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';
    if (file_exists($dbf_file)) { 
      $db = dbase_open($dbf_file, 0);
      $record_numbers = dbase_numrecords($db);
      $a = [];
      $valid = true;
      for ($i = 1; $i <= $record_numbers; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $vfpdate = vfpdate_to_carbon(trim($row['TRANDATE']));
       
        if ( $vfpdate->format('Y-m-d')==$backup->date->format('Y-m-d')) {

          if (c()->diffInDays($backup->date) < 3) { // dont check if lagpas 3 days

            $t = trim($row['TIP']);
            if (empty($t)) {
              array_push($a, 'TIPS');
            }

            $k = trim($row['CREW_KIT']);
            if (empty($k)) {
              array_push($a, 'CREW_KIT');
              // $valid = false;
            }

            $d = trim($row['CREW_DIN']);
            if (empty($d)) {
              array_push($a, 'CREW_DIN');
              // $valid = false;
            }

            if (empty($k) && empty($d))
              $valid = false;
          } 
        }
      }
      dbase_close($db);

      if (!$valid) {
      }
      

    } else {
      throw new Exception("Cannot locate CSH_AUDT.DBF"); 
    }
  }


  private function getManCost() {
    $this->getBackupCode();
    return ($this->sysinfo->man_cost < 1 || empty($this->sysinfo->man_cost)) 
    ? 650
    : (int) trim($this->sysinfo->man_cost);
  }

  private function getDailySalesDbfRowData($r){
    $row = [];

    $kit = isset($r['CREW_KIT']) ? trim($r['CREW_KIT']):0;
    $din = isset($r['CREW_DIN']) ? trim($r['CREW_DIN']):0;
    $tip = isset($r['TIP']) ? trim($r['TIP']):0;
    $trans_cnt = isset($r['TRAN_CNT']) ? trim($r['TRAN_CNT']):0;
    $man_hrs = isset($r['MAN_HRS']) ? trim($r['MAN_HRS']):0;
    $man_pay = isset($r['MAN_PAY']) ? trim($r['MAN_PAY']):0;
    $depo_cash = isset($r['DEPOSIT']) ? trim($r['DEPOSIT']):0;
    $depo_check = isset($r['DEPOSITK']) ? trim($r['DEPOSITK']):0;
    $sale_csh = isset($r['CSH_SALE']) ? trim($r['CSH_SALE']):0;
    $sale_chg = isset($r['CHG_SALE']) ? trim($r['CHG_SALE']):0;
    $sale_sig = isset($r['SIG_SALE']) ? trim($r['SIG_SALE']):0;
    $cuscnt = isset($r['CUST_CNT']) ? trim($r['CUST_CNT']):0;
    $mcost = (isset($r['MAN_COST']) && (trim($r['MAN_COST']>0)))
      ? trim($r['MAN_COST']) // mancost frm CHS_AUDT
      : $this->getManCost(); // Mancosr frm SYSINFO
    $mcost = ($mcost+0)==0 ? session('user.branchmancost'):$mcost;
    $shrt_ovr = isset($r['SHRT_OVR']) ? trim($r['SHRT_OVR']):0;

    $vfpdate    = vfpdate_to_carbon(trim($r['TRANDATE']));

    $sales      = ($r['CSH_SALE'] + $r['CHG_SALE']) + 0; // netsales: removed sign
    $empcount   = ((int)$kit + (int)$din);
    $tips       = $tip;
    // 2024-08-26 - disable to parse customer count
    // $custcount  = $cuscnt;
    $custcount  = 0;
    $headspend  = $custcount==0 ? 0:($sales/$custcount);
    $tipspct    = $sales>0 ? ($tips/$sales)*100 : 0;
    $mancost    = $mcost*$empcount;

    $mancostpct = $sales>0 ? ($mancost/$sales)*100 : 0;
    $salesemp   = ($empcount=='0.00' || $empcount=='0') ? 0 : $sales/$empcount;

    $row['branchid']  = session('user.branchid');
    $row['managerid'] = session('user.id'); // cashierid actually
    $row['date']      = $vfpdate->format('Y-m-d');
    $row['sales']     = number_format($sales, 2, '.', ''); 
    $row['crew_din']  = $din;
    $row['crew_kit']  = $kit;
    $row['empcount']  = $empcount;
    $row['tips']      = $tips;
    $row['custcount'] = $custcount;
    $row['headspend'] = number_format($headspend, 2, '.', '');
    $row['tipspct']   = number_format($tipspct, 2, '.', '');
    $row['mancost']   = number_format($mancost, 2, '.', '');
    $row['mancostpct']= number_format($mancostpct, 2, '.', '');
    $row['salesemp']  = number_format($salesemp, 2, '.', '');
    $row['trans_cnt'] = $trans_cnt;
    $row['man_hrs']   = number_format($man_hrs, 2, '.', '');
    $row['man_pay']   = number_format($man_pay, 2, '.', '');
    $row['depo_cash'] = number_format($depo_cash, 2, '.', '');
    $row['depo_check']= number_format($depo_check, 2, '.', '');
    $row['sale_csh']  = number_format($sale_csh, 2, '.', '');
    $row['sale_chg']  = number_format($sale_chg, 2, '.', '');
    $row['sale_sig']  = number_format($sale_sig, 2, '.', '');
    //$row['cospct']    = number_format(0, 2, '.', '');
    return $row;
  }


  public function parseCustomerCount(Carbon $date) {
    $dbf_file = $this->extracted_path.DS.'CHARGES.DBF';

    $cust_count = 0;
    
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);

      for ($i = 1; $i <= $record_numbers; $i++) {
        $row = dbase_get_record_with_names($db, $i);

        $vfpdate = Carbon::parse($row['ORDDATE']);

        if ($date->format('Y-m-d')==$vfpdate->format('Y-m-d')) {

          if (($row['SR_TCUST']==$row['SR_BODY']) && ($row['SR_DISC']>0)) // 4 4 78.7
            $cust_count += $row['SR_TCUST']; 
          else if ($row['SR_TCUST']>0 && $row['SR_BODY']>0 && $row['SR_DISC']>0)
            continue;
          else
            $cust_count += ($row['SR_TCUST'] + $row['SR_BODY']);

        }
      }
      dbase_close($db);
    }
    return $cust_count;
  }

    //public function postNewDailySales($branchid, Carbon $date, $c){
  public function postNewDailySales(Backup $backup){

    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      //$from = $backup->date->copy()->subDay(); // 2024-08-26
      $from = $backup->date;
      $to = $backup->date;
      //$from = $date->copy()->subDay();
      //$to = $date;

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $data = $this->getDailySalesDbfRowData($row);
        $vfpdate = Carbon::parse($data['date']);
        //$data['branchid'] = $branchid;
        $data['branchid'] = $backup->branchid;

        if ($vfpdate->gte($from) && $vfpdate->lte($to)) {
          //$c->info($vfpdate->format('Y-m-d'));

          if ($data['trans_cnt']<1) {
            $this->postCharges($vfpdate, $backup, true);
          }

          if ($data['custcount']<1) {
            $data['custcount'] = $this->parseCustomerCount($vfpdate);
            $data['headspend'] = $data['custcount']==0 ? 0:($data['sales']/$data['custcount']);
          }



          //$c->info($data['date'].' '.$data['sales'].' '.$data['custcount'].' '.$data['trans_cnt'].' '.$data['empcount'].' '.$data['mancost'].' '.$data['mancostpct']);

          $fields = ['date', 'branchid', 'managerid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend', 'crew_kit', 'crew_din', 'trans_cnt', 'man_hrs', 'man_pay', 'depo_cash', 'depo_check', 'sale_csh', 'sale_chg', 'sale_sig'];
          
          foreach (['sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend', 'crew_kit', 'crew_din', 'trans_cnt', 'man_hrs', 'man_pay', 'depo_cash', 'depo_check', 'sale_csh', 'sale_chg', 'sale_sig'] as $f) {
            if ($data[$f]<0)
              unset($data[$f]);
          }

          //$c->info(json_encode($data));
          $this->ds->firstOrNewField($data, ['date', 'branchid']);
        }
      }

      dbase_close($db);
      return true;
    }
    return false;
  }

  public function postDailySales(Backup $backup){

    //$this->logAction('function:postDailySales', '');
    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $last_ds = $this->ds->lastRecord();
      $update = 0;
      //$this->logAction('start:loop:ds', '');
      for ($i = 1; $i <= $record_numbers; $i++) {

        $row = dbase_get_record_with_names($db, $i);
        $data = $this->getDailySalesDbfRowData($row);
        $vfpdate = Carbon::parse($data['date']);

        

        //$this->logAction('loop:ds:'.$vfpdate->format('Y-m-d'), '');
        // back job on posting purchased 
        if ( $vfpdate->format('Y-m')==$backup->date->format('Y-m') // trans date equal year & mons of backup
        && $backup->date->format('Y-m-d')==$backup->date->endOfMonth()->format('Y-m-d') // if the backupdate = mon end date
        && $backup->date->lte(Carbon::parse('2016-06-01'))) // only backup less than april 1
        {
          
          try {
            $this->postPurchased($vfpdate);
          } catch(Exception $e) {
            return false;
            //throw new Exception($e->getMessage());    
          }
          
          //$this->logAction($vfpdate->format('Y-m-d'), '', base_path().DS.'logs'.DS.'GLV'.DS.$vfpdate->format('Y-m-d').'-PO.txt');
   
        } 
        //test_log('date: '. $vfpdate->format('Y-m-d'));
        /*
        // if backup is start of month, update end of last month
        if($vfpdate->format('Y-m-d')==$backup->date->copy()->subDay()->format('Y-m-d')
        && $backup->date->format('Y-m-d')==$backup->date->startOfMonth()->format('Y-m-d'))
        {
          test_log('date: '. $vfpdate->format('Y-m-d'));
          try {
            $this->postPurchased($vfpdate);
          } catch(Exception $e) {
            return false;
            //throw new Exception($e->getMessage());    
          }
        }
        */



        //$this->logAction('ds:get_last', '');
        if(is_null($last_ds)) {

          if ($this->ds->firstOrNewField($data, ['date', 'branchid']));
            $update++;
        
        } else {
          /*
          * commented: issue not update DS if the last DS is higher than backup
          */
          //if($last_ds->date->lte($vfpdate)) { //&& $last_ds->date->lte(Carbon::parse('2016-01-01'))) { 
          if( $vfpdate->format('Y-m') == $backup->date->format('Y-m')) 
          {
            // fix cust_count on boss module = 0     - 2016-11-06
            if ($data['custcount']=='0') {
              $data['custcount'] = $this->parseCustomerCount($vfpdate);
              $data['headspend'] = $data['custcount']==0 ? 0:($data['sales']/$data['custcount']);
            }
          
            if($i==$record_numbers) {


              if (isset($this->sysinfo->posupdate) 
              && vfpdate_to_carbon($this->sysinfo->posupdate)->lt(Carbon::parse('2016-07-06')))  // before sysinfo.update
              {
                if ($this->ds->firstOrNewField(array_only($data, ['date', 'branchid', 'managerid', 'sales']), ['date', 'branchid'])) {
                  $update++;
                }
              } else {
               
                
                if ($vfpdate->gt(Carbon::parse('2017-01-01')))
                  $fields = ['date', 'branchid', 'managerid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend', 'crew_kit', 'crew_din', 'trans_cnt', 'man_hrs', 'man_pay', 'depo_cash', 'depo_check'];
                else
                  $fields = ['date', 'branchid', 'managerid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend', 'crew_kit', 'crew_din', 'trans_cnt'];
                  

                if ($this->ds->firstOrNewField(array_only($data, $fields), ['date', 'branchid'])) {
                  $update++;
                  //test_log('last: '. $vfpdate->format('Y-m-d').' '.$data['custcount']);
                }
              }
            } else {
              //$this->logAction('single:lte:i!=record_numbers', '');
              if ($this->ds->firstOrNewField($data, ['date', 'branchid']))
                $update++;
            }
          }

          // if FOM update EOM
          if( $backup->date->format('Y-m-d') == $backup->date->copy()->startOfMonth()->format('Y-m-d')
          && $vfpdate->format('Y-m-d') == $backup->date->copy()->subDay()->format('Y-m-d') )
          {
            // fix cust_count on boss module = 0     - 2016-11-06
            //if ($data['custcount']=='0')
             // $data['custcount'] = $this->parseCustomerCount($vfpdate);

            //test_log('last: '. $vfpdate->format('Y-m-d').' '.$data['custcount']);
            if ($this->ds->firstOrNewField(array_only($data, 
                ['date', 'branchid', 'managerid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend', 'crew_kit', 'crew_din', 'trans_cnt']
              ), ['date', 'branchid'])) {
                $update++;
              }
          }


        }
      }
      //$this->logAction('end:loop:ds', '');
      dbase_close($db);
      return $update>0 ? $update:false;
    }

    return false;
  }




  public function postPurchased(Carbon $date) {
    
    $dbf_file = $this->extracted_path.DS.'PURCHASE.DBF';

    //$this->logAction($date->format('Y-m-d'),'post:purchased:file_exists');
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $tot_purchase = 0;
      $update = 0;
      $food_cost = 0;
      $opex = 0;
      $utang = 0;
      $saved = false;
      $ncos = 0;

      // delete if exist
      try {
        //$this->logAction($date->format('Y-m-d'), 'delete:purchased');
        $this->purchase->deleteWhere(['branchid'=>session('user.branchid'), 'date'=>$date->format('Y-m-d')]);
      } catch(Exception $e) {
        throw $e;    
      }


      try {
        //$this->logAction($date->format('Y-m-d'), 'delete:purchased2');
        $this->purchase2->deleteWhere(['branchid'=>session('user.branchid'), 'date'=>$date->format('Y-m-d'), 'save'=>0]);
      } catch(Exception $e) {
        throw $e;    
      }

      $saves = $this->purchase2->findWhere(['branchid'=>session('user.branchid'), 'date'=>$date->format('Y-m-d'), 'save'=>1]);
      if (count($saves)>0)
        $saved = true;

      //$this->logAction($date->format('Y-m-d'), 'start:loop:purchased');
      for ($i=1; $i<=$record_numbers; $i++) {

        $row = dbase_get_record_with_names($db, $i);

        try {
          //$vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
          //$vfpdate = vfpdate_to_carbon(trim($r['TRANDATE']));
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }
        

        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
          //$this->logAction($vfpdate->format('Y-m-d'), trim($row['COMP']), base_path().DS.'logs'.DS.'GLV'.DS.$vfpdate->format('Y-m-d').'-PO.txt');
          $tcost = trim($row['TCOST']);

          $attrs = [
            'comp'      => trim($row['COMP']),
            'unit'      => trim($row['UNIT']),
            'qty'       => trim($row['QTY']),
            'ucost'     => trim($row['UCOST']),
            'tcost'     => $tcost,
            'date'      => $vfpdate->format('Y-m-d'),
            'supno'     => trim($row['SUPNO']),
            'supname'   => trim($row['SUPNAME']),
            'catname'   => trim($row['CATNAME']),
            'vat'       => trim($row['VAT']),
            'terms'     => trim($row['TERMS']),
            'branchid'  => session('user.branchid')
          ];
          
          //\DB::beginTransaction();
          //$this->logAction($date->format('Y-m-d'), 'create:purchased');

          /* remove 2017-09-12
          try {
            $this->purchase->create($attrs);
          } catch(Exception $e) {
            throw $e;    
          }
          */

          $attrs['tin'] = trim($row['SUPTIN']);
          $attrs['supprefno'] = trim($row['FILLER1']);
          try {
            //$this->logAction($date->format('Y-m-d'), 'create:purchased2');
            $this->purchase2->verifyAndCreate($attrs, $saved);
          } catch(Exception $e) {
            throw $e;    
          }

          if (in_array(substr($attrs['supno'], 0, 2), $this->expense_array) && $attrs['terms']!='U')
            $food_cost += $tcost;
          if (in_array(substr($attrs['supno'], 0, 2), $this->non_cos_array) && $attrs['terms']!='U')
            $ncos += $tcost;
          if (!in_array(substr($attrs['supno'], 0, 2), $this->expense_array) && !in_array(substr($attrs['supno'], 0, 2), $this->non_cos_array) && $attrs['terms']!='U')
            $opex += $tcost;
          if ($attrs['terms']=='U')
            $utang += $tcost;
          
          //\DB::rollBack();
          $tot_purchase += $tcost;
          $update++;
        }
      }
      //$this->logAction($date->format('Y-m-d'), 'end:loop:purchased');

      try {
        //$this->logAction($date->format('Y-m-d'), 'update:ds');
        $d =  $this->ds->findWhere([
                            'branchid'=>session('user.branchid'), 
                            'date'=>$date->format('Y-m-d')],
                            ['sales'])->first();
        
        //$cospct = ($d->sales=='0.00' || $d->sales=='0') ? 0 : ($food_cost/$d->sales)*100;
        $cospct = $d->sales>0 ? ($food_cost/$d->sales)*100 : 0;

        $this->ds->firstOrNewField(['branchid'=>session('user.branchid'), 
                            'date'=>$date->format('Y-m-d'),
                            'cos'=> $food_cost,
                            'ncos'=> $ncos,
                            'cospct'=> $cospct,
                            'opex'=> $opex,
                            'utang'=> $utang,
                            'purchcost'=>$tot_purchase],
                            ['date', 'branchid']);
      } catch(Exception $e) {
        throw $e;    
      }

      

      dbase_close($db);
      unset($db);
      return $update>0 ? true:false;
    }
    return false;
  }


  public function logAction($action, $log, $logfile=NULL) {
    $logfile = !is_null($logfile) 
      ? $logfile
      : base_path().DS.'logs'.DS.session('user.branchcode').DS.now().'-log.txt';

    $dir = pathinfo($logfile, PATHINFO_DIRNAME);

    if(!is_dir($dir))
      mkdir($dir, 0775, true);

    $new = file_exists($logfile) ? false : true;
    if($new){
      $handle = fopen($logfile, 'w+');
      chmod($logfile, 0775);
    } else
      $handle = fopen($logfile, 'a');

    $ip = clientIP();
    $brw = $_SERVER['HTTP_USER_AGENT'];
    $content = date('r')." | {$ip} | {$action} | {$log} \t {$brw}\n";
    $content = "{$action} | {$log}\n";
    fwrite($handle, $content);
    fclose($handle);
  } 



  public function removeDir($dir){
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
  }


  public function removeExtratedDir() {
    if (!is_null($this->extracted_path))
      return $this->removeDir($this->extracted_path);
    else
      return false;
  }

  public function lastRecord() {
      $this->applyFilters();
      return $this->model->orderBy('uploaddate', 'DESC')->first();
  }

  public function ds(){
    return $this->ds->all();
  }
  
  








    /**
   * Return files and directories within a folder
   *
   * @param string $folder
   * @return array of [
   *    'folder' => 'path to current folder',
   *    'folderName' => 'name of just current folder',
   *    'breadCrumbs' => breadcrumb array of [ $path => $foldername ]
   *    'folders' => array of [ $path => $foldername] of each subfolder
   *    'files' => array of file details on each file in folder
   * ]
   */
  public function folderInfo($folder)
  {
    $folder = $this->cleanFolder($folder);

    $folder2 = $this->cleanFolder($this->changeRoot($folder));

    $type = $this->filetype($folder);
    $breadcrumbs = $this->breadcrumbs($folder);
    $slice = array_slice($breadcrumbs, -1);
    $folderName = current($slice);
    $breadcrumbs = array_slice($breadcrumbs, 0, -1);
    $vals = explode('/', $folder);
    $x = empty($vals[1]) ? $folderName:$vals[1];

    $subfolders = [];
    foreach (array_unique($this->disk->directories($folder2)) as $subfolder) {
      $subfolder = $this->changeRoot2($subfolder, $x);
      $subfolders["/$subfolder"] = basename($subfolder);
    }

    $files = [];
    foreach ($this->disk->files($folder2) as $path) {
        $files[] = $this->fileDetails($path);
        //$files[] = config('gi-dtr.upload_path')[app()->environment()].$path;
    }

    return compact(
      'folder',
      'folderName',
      'breadcrumbs',
      'subfolders',
      'files'
    );
  }

  public function changeRoot($folder){
    return str_replace(['files', 'pos'], session('user.branchcode'), $folder);
  }

  public function changeRoot2($folder, $type){
    return str_replace(session('user.branchcode'), $type ,$folder);
  }

  /**
   * Sanitize the folder name
   */
  protected function cleanFolder($folder)
  {

    return '/' . trim(str_replace('..', '', $folder), '/');
    //return trim(str_replace('..', '', $folder), '/');
  }

  /**
   * Return breadcrumbs to current folder
   */
  protected function breadcrumbs($folder)
  {
    $folder = trim($folder, '/');
    $crumbs = ['/' => session('user.branchcode')];

    if (empty($folder)) {
      return $crumbs;
    }

    $folders = explode('/', $folder);
    $build = '';
    foreach ($folders as $folder) {
      $build .= '/'.$folder;
      $crumbs[$build] = $folder;
    }

    return $crumbs;
  }

  /**
   * Return an array of file details for a file
   */
  protected function fileDetails($path)
  {
    //$path = '/' . ltrim($path, '/');
    $path = $path;

    return [
      'name' => basename($path),
      'fullPath' => $path,
      'realFullPath' => $this->realFullPath($path),
      'webPath' => $this->fileWebpath($path),
      'mimeType' => $this->fileMimeType($path),
      'size' => $this->fileSize($path),
      'modified' => $this->fileModified($path),
      'type' => $this->filetype($path)
    ];
  }

  public function realFullPath($path){
    //return config('gi-dtr.upload_path.pos.'.app()->environment()).$path;
    return config('gi-dtr.upload_path.web').$path;
  }



  /**
   * Return the full web path to a file
   */
  public function fileWebpath($path)
  {
    $path = rtrim('uploads', '/') . '/' .
        ltrim($path, '/');
    return url($path);
  }

  /**
   * Return the mime type
   */
  public function fileMimeType($path)
  {
      return $this->mimeDetect->findType(
        strtolower(pathinfo($path, PATHINFO_EXTENSION))
      );
  }

  public function filetype($path){
    if(strtolower(pathinfo($path, PATHINFO_EXTENSION))==='zip')
      return 'zip';
    if(strtolower(pathinfo($path, PATHINFO_EXTENSION))==='png' || 
      strtolower(pathinfo($path, PATHINFO_EXTENSION))==='jpg'  || 
      strtolower(pathinfo($path, PATHINFO_EXTENSION))==='jpeg' || 
      strtolower(pathinfo($path, PATHINFO_EXTENSION))==='gif')
      return 'img';
    return 'file';
  }

  /**
   * Return the file size
   */
  public function fileSize($path)
  {
    return $this->disk->size($path);
  }

  /**
   * Return the last modified time
   */
  public function fileModified($path)
  {
    return Carbon::createFromTimestamp(
      $this->disk->lastModified($path)
    );
  }





  // Add the 4 methods below to the class
  /**
   * Create a new directory
   */
  public function createDirectory($folder)
  {
    $folder = $this->cleanFolder($folder);

    if ($this->disk->exists($folder)) {
      return "Folder '$folder' aleady exists.";
    }

    return $this->disk->makeDirectory($folder);
  }

  /**
   * Delete a directory
   */
  public function deleteDirectory($folder)
  {
    $folder = $this->cleanFolder($folder);

    $filesFolders = array_merge(
      $this->disk->directories($folder),
      $this->disk->files($folder)
    );
    if (! empty($filesFolders)) {
      return "Directory must be empty to delete it.";
    }

    return $this->disk->deleteDirectory($folder);
  }

  /**
   * Delete a file
   */
  public function deleteFile($path)
  {
    $path = $this->cleanFolder($path);

    if (! $this->disk->exists($path)) {
      return "File does not exist.";
    }

    return $this->disk->delete($path);
  }

  /**
   * Save a file
   */
  public function saveFile($path, $content, $exist=true)
  {
    $path = $this->cleanFolder($path);

    if($exist) {
      if ($this->disk->exists($path)) {
        return "File already exists.";
      }
    }

    return $this->disk->put($path, $content);
  }

  public function exists($path){
    return $this->disk->exists($path);
  }

  /**
   * Move a file
   */
  public function moveFile($src, $target, $exist=true)
  {
    //$path = $this->cleanFolder($target);
    $path = $target;
    $dir = pathinfo($this->realFullPath($path));

    if($exist) {
      if ($this->disk->exists($path)) {
        //return "File already exists...";
        throw new Exception("File ".$dir['basename'].'.'.$dir['extension']." already exists on storage ".$this->type);
      }
    }

   

    
    if(!is_dir($dir['dirname']))
      mkdir($dir['dirname'], 0775, true); //$this->createDirectory($dir);

    //return $this->disk->move($src, $target);

    try {
      File::move($src, $this->realFullPath($path));
    }catch(Exception $e){
      throw new Exception("Error ". $e->getMessage());
    }
  }

  public function get($path){
    return file_get_contents($this->realFullPath($path));
  }






  public function extract2($src, $pwd=NULL){
     
    //$dir = $this->realFullPath($src);
    $dir = $src;
    $zip = new ZipArchive();
    $zip_status = $zip->open($dir);

    if($zip_status === true) {

      if(!is_null($pwd))
        $zip->setPassword($pwd);
      
      $path = storage_path().DS.'backup'.DS.pathinfo($src, PATHINFO_FILENAME);
      
      if(is_dir($path)) {
        $this->removeDir($path);
      }
      mkdir($path, 0777, true);
       
      $this->extracted_path = $path;

      if(!$zip->extractTo($path)) {
        $zip->close();
        return false;
      }

      

      $zip->close();

      return true;
    } else {
      return false;
    }
  }


  /*
  *  postDailySales2 dont use session('user.branchid')
  *  and for Command Line
  * 
  */
  public function postDailySales2(Backup $backup){

      //$this->logAction('function:postDailySales', '');
      $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

      if (file_exists($dbf_file)) {
        $db = dbase_open($dbf_file, 0);
        $header = dbase_get_header_info($db);
        $record_numbers = dbase_numrecords($db);
        //$last_ds = $this->ds->lastRecord();
        $last_ds = NULL;
        $update = 0;

        for ($i = 1; $i <= $record_numbers; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $data = $this->getDailySalesDbfRowData($row);
          $vfpdate = Carbon::parse($data['date']);

          // back job on posting purchased 
          if ( $vfpdate->format('Y-m')==$backup->date->format('Y-m') // trans date equal year & mons of backup
          //&& $backup->date->format('Y-m-d')==$backup->date->endOfMonth()->format('Y-m-d') // if the backupdate = mon end date
          && $backup->date->lte(Carbon::parse('2016-06-01'))) // only backup less than april 1
          {
            
            try {
              $this->postPurchased2($vfpdate, $backup);
            } catch(Exception $e) {
              dbase_close($db);
              throw new Exception($e->getMessage());    
            }
          } 
          
          if(is_null($last_ds)) {

            if ($this->ds->firstOrNewField($data, ['date', 'branchid']));
              $update++;
          
          } else {
            /*
            * commented: issue not update DS if the last DS is higher than backup
            */
            //if($last_ds->date->lte($vfpdate)) { //&& $last_ds->date->lte(Carbon::parse('2016-01-01'))) { 
            if($vfpdate->format('Y-m')==$backup->date->format('Y-m')) {

              //$this->logAction('single:lte', '');
            
              if($i==$record_numbers) {
               
                //$this->logAction('single:lte:i==record_numbers', '');
                if ($this->ds->firstOrNewField(array_only($data, ['date', 'branchid', 'managerid', 'sales']), ['date', 'branchid']))
                  $update++;

              } else {
                
                //$this->logAction('single:lte:i!=record_numbers', '');
                if ($this->ds->firstOrNewField($data, ['date', 'branchid']))
                  $update++;

              }
            }


          }
        }
        //$this->logAction('end:loop:ds', '');
        dbase_close($db);
        return $update>0 ? true:false;
      }

      return false;
  }


  /*
  *  postPurchased2 dont use session('user.branchid')
  *  and for Command Line
  * 
  */
  public function postPurchased2(Carbon $date, Backup $backup) {
    
    $dbf_file = $this->extracted_path.DS.'PURCHASE.DBF';

    //$this->logAction($date->format('Y-m-d'),'post:purchased:file_exists');
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $tot_purchase = 0;
      $update = 0;
      $food_cost = 0;
      $utang = 0;
      $opex = 0;
      $ncos = 0;

      // delete if exist
      try {
        //$this->logAction($date->format('Y-m-d'), 'delete:purchased');
        $this->purchase->deleteWhere(['branchid'=>$backup->branchid, 'date'=>$date->format('Y-m-d')]);
      } catch(Exception $e) {
        dbase_close($db);
        throw new Exception($e->getMessage());    
      }


      try {
        //$this->logAction($date->format('Y-m-d'), 'delete:purchased2');
        $this->purchase2->deleteWhere(['branchid'=>$backup->branchid, 'date'=>$date->format('Y-m-d')]);
      } catch(Exception $e) {
        dbase_close($db);
        throw new Exception($e->getMessage());    
      }


      for ($i = 1; $i <= $record_numbers; $i++) {

        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }

        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {

          $tcost = trim($row['TCOST']);

          $attrs = [
            'comp'      => trim($row['COMP']),
            'unit'      => trim($row['UNIT']),
            'qty'       => trim($row['QTY']),
            'ucost'     => trim($row['UCOST']),
            'tcost'     => $tcost,
            'date'      => $vfpdate->format('Y-m-d'),
            'supno'     => trim($row['SUPNO']),
            'supname'   => trim($row['SUPNAME']),
            'catname'   => trim($row['CATNAME']),
            'vat'       => trim($row['VAT']),
            'terms'     => trim($row['TERMS']),
            'branchid'  => $backup->branchid
          ];
          
          /* remove from 2017-09-12
          try {
            $this->purchase->create($attrs);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }
          */

          $attrs['tin'] = trim($row['SUPTIN']);
          $attrs['supprefno'] = trim($row['FILLER1']);
          try {
            $this->purchase2->verifyAndCreate($attrs);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }

          if (in_array(substr($attrs['supno'], 0, 2), $this->expense_array) && $attrs['terms']!='U')
            $food_cost += $tcost;
          if (in_array(substr($attrs['supno'], 0, 2), $this->non_cos_array) && $attrs['terms']!='U')
            $ncos += $tcost;
          if (!in_array(substr($attrs['supno'], 0, 2), $this->expense_array) && !in_array(substr($attrs['supno'], 0, 2), $this->non_cos_array) && $attrs['terms']!='U')
            $opex += $tcost;
          if ($attrs['terms']=='U')
            $utang += $tcost;

          
          //\DB::rollBack();
          $tot_purchase += $tcost;
          $update++;
        }
      }

      try {
        
        // $this->ds->skipFilters()  // added bec no session('user.branchid')
        //$d =  $this->ds->skipFilters()->findWhere(['branchid'=>$backup->branchid, 
        $d =  $this->ds2->skipCriteria()->findWhere(['branchid'=>$backup->branchid, 
                              'date'=>$date->format('Y-m-d')],
                              ['sales'])->first();
          
        $cospct = ($d->sales=='0.00' || $d->sales=='0') ? 0 : ($food_cost/$d->sales)*100;

        $this->ds2->firstOrNewField(['branchid'=>$backup->branchid, 
                            'date'=>$date->format('Y-m-d'),
                            'cos'=> $food_cost,
                            'ncos'=> $ncos,
                            'cospct'=> $cospct,
                            'opex'=> $opex,
                            'utang'=> $utang,
                            'purchcost'=>$tot_purchase],
                            ['date', 'branchid']);
      } catch(Exception $e) {
        dbase_close($db);
        throw $e;    
      }

      

      dbase_close($db);
      return $update>0 ? true:false;
    }
    return false;
  }


  public function postCashAudit(Carbon $date, Backup $backup){

    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);        
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      $data = [];
      

      for ($i=1; $i<=$record_numbers; $i++) {
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['TRANDATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }

        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) { // if salesmtd date == backup date
          $data = $this->getDailySalesDbfRowData($row);
          $data['date']       = $date->format('Y-m-d');
          $data['branchid']   = $backup->branchid;
          
          
          if ($this->ds->firstOrNewField(array_only($data, 
                    ['date', 'branchid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend', 'crew_kit', 'crew_din', 'shrt_ovr']
                  ), ['date', 'branchid'])) {
            $update++;

          }
        }
      }
      dbase_close($db);
      unset($data);
      return $update;
    }
    return false;
  }



  public function postSalesmtd(Carbon $date, Backup $backup) {

    $dbf_file = $this->extracted_path.DS.'SALESMTD.DBF';

    if (file_exists($dbf_file)) {
      //$this->logAction('posting', 'post:salesmtd');
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      // delete salesmtd (branchid, date) if exist
      try {
        //$this->logAction('DELETE', $backup->branchid.' '.$date->format('Y-m-d'));

        /*
        * Activate only if salesmtd has a searate table per year
        * e.g. salesmtd, salesmtd2019, salesmtd2020
        *        
        if ($date->gte(c('2020-07-01'))) {
          $n = new \App\Models\Salesmtd;
          $n->setTable('salesmtd2020');
          $n->where(['branch_id'=>$backup->branchid, 'orddate'=>$date->format('Y-m-d')])->delete();
        }
        */
        
        $this->salesmtdCtrl->deleteWhere(['branch_id'=>$backup->branchid, 'orddate'=>$date->format('Y-m-d')]);
        // $this->changeItem->deleteWhere(['branch_id'=>$backup->branchid, 'date'=>$date->format('Y-m-d')]); // removed 08/01/2023 - ImpoterChangeItem
      } catch(Exception $e) {
        dbase_close($db);
        throw new Exception($e->getMessage());    
      }

      $ctr = 0;
      $ds = [];
      $ds['slsmtd_totgrs'] = 0;
      $ds['food_sales'] = 0;
      $ds['date']       = $date->format('Y-m-d');
      $ds['branchid']   = $backup->branchid;
      
      for ($i=1; $i<=$record_numbers; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        //$this->logAction('-', $row['ORDDATE']);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }

        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) { // if salesmtd date == backup date
          $data = $this->salesmtdCtrl->associateAttributes($row);
          $data['branch_id'] = $backup->branchid;

          if ($ctr==0) {
            $ds['opened_at'] = $data['ordtime'];
            $ds['closed_at'] = $data['ordtime'];
          }

          if (c($ds['opened_at'])->gt(c($data['ordtime'])))
            $ds['opened_at'] = $data['ordtime'];

          if (c($ds['closed_at'])->lt(c($data['ordtime'])))
            $ds['closed_at'] = $data['ordtime'];

          try {
            //$this->logAction($data['orddate'], ' create:salesmtd');
            $this->salesmtdCtrl->create($data);
            $update++;
          } catch (QueryException $e) {
            dbase_close($db);
            throw new Exception('Error! Please try to upload after 30-60 minutes.');   
            return false;
          } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('salesmtd: '.$e->getMessage());   
            return false;   
          }
          $ds['slsmtd_totgrs'] += $data['grsamt'];

          if (strtolower($data['prodcat'])=='foods' && !strpos(strtolower($data['product']), 'pepsi'))
            $ds['food_sales'] += $data['grsamt'];

          $ctr++;
        }
      }

      // update dailysales
      try {
        $this->ds->firstOrNewField($ds, ['date', 'branchid']);
      } catch(Exception $e) {
        dbase_close($db);
        throw new Exception('salesmtd:ds: '.$e->getMessage());    
      }

      dbase_close($db);
      unset($ds);
      return $update;
    }
    return false;  
  }



  public function postCharges(Carbon $date, Backup $backup, $parseCustCnt=false) {

    //$dbf_file = $this->extracted_path.DS.'CHARGES.DBF';
    
    // delete charges (branchid, date) if exist
    try {
      //$this->logAction('DELETE', $backup->branchid.' '.$date->format('Y-m-d'));
      $this->charges->deleteWhere(['branch_id'=>$backup->branchid, 'orddate'=>$date->format('Y-m-d')]);
      } catch(Exception $e) {
      throw new Exception('charges: '.$e->getMessage());    
    }

    $ds = [];
    $ds['bank_totchrg'] = 0;
    $ds['chrg_total']   = 0;
    $ds['chrg_csh']     = 0;
    $ds['chrg_chrg']    = 0;
    $ds['chrg_othr']    = 0;
    $ds['disc_totamt']  = 0;
    $ds['date']         = $date->format('Y-m-d');
    $ds['branchid']     = $backup->branchid;

    $ds['grab']  = 0;
    $ds['grabc']  = 0;
    $ds['panda']  = 0;
    $ds['zap']  = 0;
    $ds['smo']  = 0;
    $ds['maya']  = 0;
    $ds['zap_delfee']  = 0;
    $ds['totdeliver']  = 0;
    $ds['tot_dine'] = 0;
    $ds['tot_togo'] = 0;
    $ds['tot_onlrid'] = 0;
    $ds['tot_osaletype'] = 0;
    $ds['zap_sales'] = 0;
    $ds['vat_xmpt'] = 0;
    $ds['pax_dine'] = 0;
    $ds['pax_togo'] = 0;
    $ds['pax_onlrid'] = 0;
    $ds['pax_osaletype'] = 0;
    $ds['trx_dine'] = 0;
    $ds['trx_togo'] = 0;
    $ds['trx_onlrid'] = 0;
    $ds['trx_osaletype'] = 0;

    
    if ($date->gt(Carbon::parse('2016-05-18')) && $date->lt(Carbon::parse('2016-10-31'))) // same sas line 1226
      $ds['custcount'] = 0;
      

    try {
      $c = $this->postRawCharges($date, $backup);
    } catch(Exception $e) {
      throw new Exception('postCharges:charges: '.$e->getMessage());    
    }

    try {
      $s = $this->postRawSigned($date, $backup);
    } catch(Exception $e) {
      throw new Exception('postCharges:signed: '.$e->getMessage());    
    }


    $ds['bank_totchrg'] = $c['bank_totchrg'] + $s['bank_totchrg'];
    $ds['chrg_total']   = $c['chrg_total'] + $s['chrg_total'];
    $ds['chrg_csh']     = $c['chrg_csh'] + $s['chrg_csh'];
    $ds['chrg_chrg']    = $c['chrg_chrg'] + $s['chrg_chrg'];
    $ds['chrg_othr']    = $c['chrg_othr'] + $s['chrg_othr'];
    $ds['disc_totamt']  = $c['disc_totamt'] + $s['disc_totamt'];
    $ds['sales'] = $ds['chrg_csh'] + $ds['chrg_chrg'];

    $ds['grab']   = $c['grab'] + $s['grab'];
    $ds['panda']  = $c['panda'] + $s['panda'];
    $ds['grabc']  = $c['grabc'] + $s['grabc'];
    $ds['zap']    = $c['zap'] + $s['zap'];
    $ds['zap_delfee'] = $c['zap_delfee'] + $s['zap_delfee'];
    $ds['smo']    = $c['smo'];
    $ds['maya']   = $c['maya'];
    $ds['totdeliver'] = $c['totdeliver'] + $s['totdeliver'];
    $ds['tot_dine'] =  $c['tot_dine'];
    $ds['tot_togo'] =  $c['tot_togo'];
    $ds['tot_onlrid'] =  $c['tot_onlrid'];
    $ds['tot_osaletype'] =  $c['tot_osaletype'];
    $ds['zap_sales']  = $c['zap_sales'] + $s['zap_sales'];
    $ds['vat_xmpt']  = $c['vat_xmpt'] + $s['vat_xmpt'];
    $ds['pax_dine'] =  $c['pax_dine'];
    $ds['pax_togo'] =  $c['pax_togo'];
    $ds['pax_onlrid'] =  $c['pax_onlrid'];
    $ds['pax_osaletype'] =  $c['pax_osaletype'];
    $ds['trx_dine'] =  $c['trx_dine'];
    $ds['trx_togo'] =  $c['trx_togo'];
    $ds['trx_onlrid'] =  $c['trx_onlrid'];
    $ds['trx_osaletype'] =  $c['trx_osaletype'];


    // remove the date bec of $this->postNewDailySales
    #if ($date->gt(Carbon::parse('2016-05-18')) && $date->lt(Carbon::parse('2016-10-31'))) {
    if ($parseCustCnt) {
      $ds['custcount']  = $c['custcount'] + $s['custcount'];
      $ds['headspend']  = ($ds['custcount'] > 0) ? $ds['chrg_total'] / $ds['custcount'] : 0 ;
      $ds['trans_cnt']  = $c['ctr'] + $s['ctr'];
    }


    // update dailysales
    try {
      $this->ds->firstOrNewField($ds, ['date', 'branchid']);
      } catch(Exception $e) {
      //dbase_close($db);
      throw new Exception('charges:ds: '.$e->getMessage());    
    }
    //unset($ds);
    return false;
  }


  public function postRawCharges(Carbon $date, Backup $backup) {

    $dbf_file = $this->extracted_path.DS.'CHARGES.DBF';

    if (file_exists($dbf_file)) {
      //$this->logAction('posting', 'post:charges');
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;
      
      $ds = [];
      $ds['bank_totchrg'] = 0;
      $ds['chrg_total'] = 0;
      $ds['chrg_csh']   = 0;
      $ds['chrg_chrg']  = 0;
      $ds['chrg_othr']  = 0;
      $ds['disc_totamt']  = 0;
      $ds['custcount']  = 0;

      $ds['panda'] = 0;
      $ds['grab'] = 0;
      $ds['grabc'] = 0;
      $ds['zap'] = 0;
      $ds['smo'] = 0;
      $ds['maya'] = 0;
      $ds['zap_delfee'] = 0;
      $ds['totdeliver'] = 0;
      $ds['tot_dine'] = 0;
      $ds['tot_togo'] = 0;
      $ds['tot_onlrid'] = 0;
      $ds['tot_osaletype'] = 0;
      $ds['zap_sales'] = 0;
      $ds['vat_xmpt'] = 0;
      $ds['pax_dine'] = 0;
      $ds['pax_togo'] = 0;
      $ds['pax_onlrid'] = 0;
      $ds['pax_osaletype'] = 0;
      $ds['trx_dine'] = 0;
      $ds['trx_togo'] = 0;
      $ds['trx_onlrid'] = 0;
      $ds['trx_osaletype'] = 0;

      for ($i=1; $i<=$record_numbers; $i++) {
        
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          continue;
        }
        
        
        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
          $data = $this->charges->associateAttributes($row);
          $data['branch_id'] = $backup->branchid;

          try {
            //$this->logAction($data['orddate'], ' create:charges');
            $this->charges->create($data);
            $update++;
            } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('charges: '.$e->getMessage());  
            return false;  
          }

          switch (strtolower($data['terms'])) {
            case 'cash':
              $ds['chrg_csh'] += $data['tot_chrg'];
              break;
            case 'charge':
              $ds['chrg_chrg'] += $data['tot_chrg'];
              break;
            default:
              $ds['chrg_othr'] += $data['tot_chrg'];
              break;
          }

          switch (strtolower($data['saletype'])) {
            case 'dinein':
              $ds['tot_dine'] += $data['tot_chrg'];
              $ds['pax_dine'] += $data['custcount'];
              $ds['trx_dine'] ++;
              break;
            case 'tkeout':
              $ds['tot_togo'] += $data['tot_chrg'];
              $ds['pax_togo'] += $data['custcount'];
              $ds['trx_togo'] ++;
              break;
            case 'onlrid':
            case 'calwed':
            case 'onlwed':
              $ds['tot_onlrid'] += $data['tot_chrg'];
              $ds['pax_onlrid'] += $data['custcount'];
              $ds['trx_onlrid'] ++;
              // if ($data['terms']!='CHARGE' && strlen($data['tblno'])>4 && (starts_with(stl($data['tblno']), 'z') || starts_with(stl($data['tblno']), 'p') || starts_with(stl($data['tblno']), 'g') || starts_with(stl($data['tblno']), 's'))) {
              //   test_log($data['tblno'].' '.$data['terms'].' '.$data['saletype'].' '.$data['chrg_type']);
              // }
              if ((starts_with(stl($data['tblno']), 'z') || starts_with(stl($data['tblno']), 'p') || starts_with(stl($data['tblno']), 'g') || starts_with(stl($data['tblno']), 's'))
                  && (strlen($data['tblno'])>4) && (strtolower($data['terms'])!='charge')) {
                  // test_log($data['tblno'].' '.$data['terms'].' '.$data['saletype'].' '.$data['chrg_type']);
                  $ds['totdeliver'] += $data['tot_chrg'];
                }
              break;
            default:
              $ds['tot_osaletype'] += $data['tot_chrg'];
              $ds['pax_osaletype'] += $data['custcount'];
              $ds['trx_osaletype'] ++;
              break;
          }
          
          $ds['chrg_total']   += $data['tot_chrg'];
          $ds['bank_totchrg'] += $data['bank_chrg'];
          $ds['disc_totamt']  += $data['disc_amt'];
          $ds['custcount']    += $data['custcount'];
          $ds['vat_xmpt']    += $data['vat_xmpt'];

          if (strtolower($data['terms'])=='charge') {
            switch (strtolower($data['chrg_type'])) {
              case 'panda':
                $ds['panda'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grab':
                $ds['grab'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grabc':
                $ds['grabc'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
              case 'maya':
                $ds['maya'] += $data['tot_chrg'];
                break;
              case 'smo-del':
                $ds['smo'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'smo-pu':
                $ds['smo'] += $data['tot_chrg'];
                break;
              case 'zap':
              case 'cod-panda':
              case 'cod-grab':
              case 'cod-3p':
              case 'zap-panda':
              case 'zap-grab':
              case 'zap-3p':
                $ds['zap'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                $ds['zap_delfee'] += $data['delivery_fee'];
                break;
            }
          }

          if (starts_with(strtolower($data['tblno']), 'z') && (substr($data['tblno'], -4) > 0))
            $ds['zap_sales'] += $data['tot_chrg'];

        }
      }
      $ds['ctr'] = $update;
      
      dbase_close($db);
    }
    return $ds;
  }

  public function postRawSigned(Carbon $date, Backup $backup) {

    $dbf_file = $this->extracted_path.DS.'SIGNED.DBF';

    if (file_exists($dbf_file)) {
      //$this->logAction('posting', 'post:charges');
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;
      
      $ds = [];
      $ds['bank_totchrg'] = 0;
      $ds['chrg_total'] = 0;
      $ds['chrg_csh']   = 0;
      $ds['chrg_chrg']  = 0;
      $ds['chrg_othr']  = 0;
      $ds['disc_totamt']  = 0;
      $ds['custcount']  = 0;

      $ds['panda'] = 0;
      $ds['grab'] = 0;
      $ds['grabc'] = 0;
      $ds['zap'] = 0;
      $ds['zap_delfee'] = 0;
      $ds['totdeliver'] = 0;
      $ds['zap_sales'] = 0;
      $ds['vat_xmpt'] = 0;

      for ($i=1; $i<=$record_numbers; $i++) {
        
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }
        
        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
          $data = $this->charges->associateAttributes($row);
          $data['branch_id'] = $backup->branchid;

          try {
            //$this->logAction($data['orddate'], ' create:charges');
            $this->charges->create($data);
            $update++;
            } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('postRawSigned: '.$e->getMessage());  
            return false;  
          }

          switch (strtolower($data['terms'])) {
            case 'cash':
              $ds['chrg_csh'] += $data['tot_chrg'];
              break;
            case 'charge':
              $ds['chrg_chrg'] += $data['tot_chrg'];
              break;
            default:
              $ds['chrg_othr'] += $data['tot_chrg'];
              break;
          }

          $ds['chrg_total'] += $data['tot_chrg'];
          $ds['bank_totchrg'] += $data['bank_chrg'];
          $ds['disc_totamt']  += $data['disc_amt'];
          $ds['custcount']    += $data['custcount'];
          $ds['vat_xmpt']    += $data['vat_xmpt'];

          if (strtolower($data['terms'])=='charge') {
            switch (strtolower($data['chrg_type'])) {
              case 'panda':
                $ds['panda'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grab':
                $ds['grab'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grabc':
                $ds['grabc'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'zap':
              case 'cod-panda':
              case 'cod-grab':
              case 'cod-3p':
              case 'zap-panda':
              case 'zap-grab':
              case 'zap-3p':
                $ds['zap'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                $ds['zap_delfee'] += $data['delivery_fee'];
                break;
            }
          }

          if (starts_with(strtolower($data['tblno']), 'z') && (substr($data['tblno'], -4) > 0))
            $ds['zap_sales'] += $data['tot_chrg'];

        }
      }
      $ds['ctr'] = $update;

      dbase_close($db);
    }
    return $ds;
  }


  public function postTransfer($branchid, Carbon $from, Carbon $to) {

    $dbf_file = $this->extracted_path.DS.'TRANSFER.DBF';
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $trans = 0;
      $curr_date = null;
      $ds = [];

      $ds['transcost'] = 0;
      $ds['transcos'] = 0;
      $ds['transncos'] = 0;
      $ds['emp_meal'] = 0;
      $ds['branchid'] = $branchid;
      $test_cos = 0;
      $test_emp = 0;

      if (is_null(session('user.branchcode'))) {
        $b = \App\Models\Branch::find($branchid);

        if (is_null($b))
          throw new \Exception('Branch not found on PosUploadRepository::postTransfer');

        $brcode = $b->code;
      } else
        $brcode = session('user.branchcode');

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $data = $this->transfer->associateAttributes($row);
        $data['branchid'] = $branchid;

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

        if ($vfpdate->gte($from) && $vfpdate->lte($to)) {

          if (is_null($curr_date)) {
            $curr_date = $vfpdate;
            $trans = 1;

            try {
              $this->transfer->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
          }

          if ($curr_date->eq($vfpdate)) {

            $trans++;
            //if ($data['tcost']>0)
              $ds['transcost'] += $data['tcost'];

            //if (in_array(substr($data['supno'], 0, 2), $this->expense_array) && $data['tcost']>0) {
            if (in_array(substr($data['supno'], 0, 2), $this->expense_array)) {
              $ds['transcos'] += $data['tcost'];
              if (strtolower(substr($data['supno'], 2, 3))==strtolower($brcode))
                $ds['emp_meal'] += $data['tcost'];
            }   
            //if (in_array(substr($data['supno'], 0, 2), $this->non_cos_array) && $data['tcost']>0) 
            if (in_array(substr($data['supno'], 0, 2), $this->non_cos_array)) 
              $ds['transncos'] += $data['tcost'];


            if ($i==$recno) {
              // $cospct = $d->sales>0 ? ($food_cost/$d->sales)*100 : 0;
              $ds['date'] = $curr_date->format('Y-m-d');

              $curr_ds = $this->ds2->findWhere(array_only($ds, ['date', 'branchid']), ['cos', 'sales'])->first();
              $cos = ($curr_ds->cos - $ds['transcos']);
              //test_log($curr_date->format('Y-m-d').' '.$curr_ds->cos.'-'.$ds['transcos'].' 1');
              //$ds['cospct'] = $cos;
              //$ds['cospct'] = $curr_ds->sales>0 ? ($cos/$curr_ds->sales)*100 : 0;

              $this->ds->firstOrNewField($ds, ['date', 'branchid']);
            }

          } else {
            
            $ds['date'] = $curr_date->format('Y-m-d');  

            $curr_ds = $this->ds2->findWhere(array_only($ds, ['date', 'branchid']), ['cos', 'sales'])->first();
            //test_log($curr_date->format('Y-m-d').' '.$curr_ds->cos.'-'.$ds['transcos'].' 2');
            $cos = ($curr_ds->cos - $ds['transcos']);
            //$ds['cospct'] = 0;

            $this->ds->firstOrNewField($ds, ['date', 'branchid']);
            
            $curr_date = $vfpdate;          
            $trans=1;
            if ($data['tcost']>0)
             $ds['transcost'] = $data['tcost'];
            else 
             $ds['transcost'] = 0;

            $ds['transcos'] = 0;
            $ds['transncos'] = 0;
            $ds['emp_meal'] = 0;

            if (in_array(substr($data['supno'], 0, 2), $this->expense_array) && $data['tcost']>0) {
              $ds['transcos'] = $data['tcost'];
              if (strtolower(substr($data['supno'], 2, 3))==strtolower(session('user.branchcode')))
                $ds['emp_meal'] = $data['tcost'];
            } 
            if (in_array(substr($data['supno'], 0, 2), $this->non_cos_array) && $data['tcost']>0) 
              $ds['transncos'] = $data['tcost'];
            
            try {
              $this->transfer->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
          }
          
          $data['to'] = $this->deliveredTo(substr($data['supno'], 2, 3), $data);
          try {
            $this->transfer->verifyAndCreate($data);
            $update++;
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }
        }
      }

      dbase_close($db);
      unset($db);
      return $update>0 ? true:false;
    }
    return false;
  }

  // prototype
  public function dbase($dbf) {
    $obj = new StdClass;
    $obj->filepath = $this->extracted_path.DS.$dbf;
    if (file_exists($obj->filepath)) {
      $obj->dbf = dbase_open($obj->filepath, 0);
      $obj->headers = dbase_get_header_info($obj->dbf);
      $obj->numrecords = dbase_numrecords($obj->dbf);
      dbase_close($obj->filepath);
      return $obj;
    }
    return false;
  }

  public function updateDailySalesManpower(Carbon $date, Backup $backup) {

    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      for ($i=1; $i<=$record_numbers; $i++) {
        $r = dbase_get_record_with_names($db, $i);

        try {
          //$vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
          $vfpdate = vfpdate_to_carbon(trim($r['TRANDATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }

        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {

          $ds = [
            'date'      => $date->format('Y-m-d'),
            'branchid'  => $backup->branchid,
            'crew_kit'  => isset($r['CREW_KIT']) ? trim($r['CREW_KIT']):0,
            'crew_din'  => isset($r['CREW_DIN']) ? trim($r['CREW_DIN']):0
          ];

          try {
            $this->ds->firstOrNewField($ds, ['date', 'branchid']);
          } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('updateDailySalesManpower: '.$e->getMessage());    
            return false;   
          }


        }
      } // end: for

     
      dbase_close($db);
      unset($ds);
      return $update;
    }
    return false;  
  }

  public function updateDailySalesTransCount(Carbon $date, Backup $backup) {

    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      for ($i=1; $i<=$record_numbers; $i++) {
        $r = dbase_get_record_with_names($db, $i);

        try {
          //$vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
          $vfpdate = vfpdate_to_carbon(trim($r['TRANDATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }

        //if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
        if ($vfpdate->gt(Carbon::parse('2016-11-30'))) {

          // update trans_cnt date > 2016-11-30
          $ds = [
            'date'      => $vfpdate->format('Y-m-d'),
            'branchid'  => $backup->branchid,
            'trans_cnt' => isset($r['TRAN_CNT']) ? trim($r['TRAN_CNT']):0
          ];

          // include update trans_cnt date > 2017-01-31
          if ($vfpdate->gt(Carbon::parse('2017-01-31'))) {
            $ds['man_hrs'] = isset($r['MAN_HRS']) ? trim($r['MAN_HRS']):0;
            $ds['man_pay'] = isset($r['MAN_PAY']) ? trim($r['MAN_PAY']):0;
          }

          try {
            $this->ds->firstOrNewField($ds, ['date', 'branchid']);
          } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('updateDailySalesTransCount: '.$e->getMessage());    
            return false;   
          }


        }
      } // end: for

     
      dbase_close($db);
      unset($ds);
      return $update;
    }
    return false;  
  }

  public function updateDeposits(Carbon $date, Backup $backup) {

    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      for ($i=1; $i<=$record_numbers; $i++) {
        $r = dbase_get_record_with_names($db, $i);

        try {
          //$vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
          $vfpdate = vfpdate_to_carbon(trim($r['TRANDATE']));
        } catch(Exception $e) {
          $vfpdate = $date->copy()->subDay();
        }

        
        $ds = [
          'date'      => $vfpdate->format('Y-m-d'),
          'branchid'  => $backup->branchid,
          'depo_cash' => isset($r['DEPOSIT']) ? trim($r['DEPOSIT']):0,
          'depo_check'=> isset($r['DEPOSITK']) ? trim($r['DEPOSITK']):0,
        ];


        try {
          $this->ds->firstOrNewField($ds, ['date', 'branchid']);
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception('updateDailySalesTransCount: '.$e->getMessage());    
          return false;   
        }


        
      } // end: for

     
      dbase_close($db);
      unset($ds);
      return $update;
    }
    return false;  
  }

  public function updateProductsTable() {

    $dbf_file = $this->extracted_path.DS.'PRODUCTS.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      $this->salesmtdCtrl->productStatusInit(); // update product.status=0
      for ($i=1; $i<=$record_numbers; $i++) {
        $r = dbase_get_record_with_names($db, $i);

        $attrs = [
          'product'     => isset($r['PRODNAME']) ? trim($r['PRODNAME']):'',
          'productcode' => isset($r['PRODNO']) ? trim($r['PRODNO']):'',
          'prodcat'     => isset($r['CATNAME']) ? trim($r['CATNAME']):'',
          'menucat'     => isset($r['RACHEL']) ? trim($r['RACHEL']):'',
          'ucost'       => isset($r['UCOST']) ? trim($r['UCOST']):0,
          'uprice'      => isset($r['UPRICE']) ? trim($r['UPRICE']):0,
          'status'      => 1,
        ];

        try {
          $product = $this->salesmtdCtrl->importProduct($attrs);
          $update++;
        } catch(Exception $e) {
          dbase_close($db);
          throw new Exception('updateProductsTable: '.$e->getMessage());    
          return false;   
        }
      } // end: for
     
      dbase_close($db);
      unset($ds);
      return $update;
    }
    return false;  
  }

  public function postKitlog($branchid, Carbon $date) {
    
    // $cnt =  $this->kitlogImporter->import($branchid, $date, $this->extracted_path);
    $kitlogImporter = $this->dbfImporter->invoke('kitlog');
    // return dd($kitlogImporter);
    $cnt = $kitlogImporter->import($branchid, $date, $this->extracted_path);

    if ($cnt>0) 
      $this->ds->firstOrNewField(['date'=>$date->format('Y-m-d'), 'branchid'=> $branchid, 'kitlog'=>1], ['date', 'branchid']);
    return $cnt;
  }


  public function postBegBal($branchid, Carbon $date) {
    
    $importer = $this->dbfImporter->invoke('begbal');
    $cnt = $importer->import($branchid, $date, $this->extracted_path);

    return $cnt;
  }

  public function postCashAudit2($branchid, Carbon $date) {
    
    $kitlogImporter = $this->dbfImporter->invoke('cash_audit');
    $cnt = $kitlogImporter->importByDr($branchid, $date, $date, $this->extracted_path);

    return $cnt;
  }

  public function processAuditReport($brcode) {
    $dbf_file = $this->extracted_path.DS.'AUDT_'.$brcode.'.PDF';
    return file_exists($dbf_file) ? $dbf_file : false;
  }

  public function processZReadReport($brcode) {
    $dbf_file = $this->extracted_path.DS.'READ_'.$brcode.'.PDF';
    return file_exists($dbf_file) ? $dbf_file : false;
  }

  public function processCashAudtReport($brcode) {
    $dbf_file = $this->extracted_path.DS.'CASH_'.$brcode.'.PDF';
    return file_exists($dbf_file) ? $dbf_file : false;
  }

  #########################################################################################################################################################################################################
  ############## for App\Command\Backlog\MonthDaily #######################################################################################################################################################
  #########################################################################################################################################################################################################
  private function getDays(Carbon $from, Carbon $to) {
    $fr = $from->copy();
    $days = [];
    do
      array_push($days, Carbon::parse($fr->format('Y-m-d').' 00:00:00'));
    while ($fr->addDay() <= $to);
    return $days;
  }

  public function backlogDailySales($branchid, Carbon $from, Carbon $to, $c) {

    $c->info('backlogDailySales start: '.$branchid.' - '.$from.' - '.$to);

    //$this->logAction('function:postDailySales', '');
    $dbf_file = $this->extracted_path.DS.'CSH_AUDT.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $record_numbers = dbase_numrecords($db);
      $update = 0;

      $c->info('record_numbers: '.$record_numbers);

      for ($i=1; $i<=$record_numbers; $i++) {
        $row = dbase_get_record_with_names($db, $i);

        // $c->info('record_number: '.$i);
        
        try {
          $vfpdate = vfpdate_to_carbon(trim($row['TRANDATE']));
        } catch(Exception $e) {
          continue;
        }

        // $row = dbase_get_record_with_names($db, $i);
        // $data = $this->getDailySalesDbfRowData($row);
        // $vfpdate = Carbon::parse($data['date']);


        // $c->info('TRANDATE '.$row['TRANDATE']);


        // add day to include last month's EoD

        // if ($vfpdate->copy()->addDay()->gte($from) && $vfpdate->lte($to)) {
        if ($vfpdate->gte($from) && $vfpdate->lte($to)) {
          $data = $this->getDailySalesDbfRowData($row);
          
          // $c->info('backlogDailySales loop: '.$vfpdate->format('Y-m-d').' - '.$from->format('Y-m-d').' - '.$to->format('Y-m-d'));
          // $c->info($data['date'].' '.$data['sales'].' '.$data['custcount'].' '.$data['trans_cnt'].' '.$data['empcount'].' '.$data['mancost'].' '.$data['mancostpct']);

          // 2024-08-16 - POS custcount not tally, rerun manually compute
          $data['custcount'] = 0;
          $data['trans_cnt'] = 0;

          $fields = ['date', 'branchid', 'managerid', 'sales', 'empcount', 'tips', 'tipspct', 'mancost', 'mancostpct', 'salesemp', 'custcount', 'headspend', 'crew_kit', 'crew_din', 'trans_cnt', 'man_hrs', 'man_pay', 'depo_cash', 'depo_check', 'sale_csh', 'sale_chg', 'sale_sig'];

          // $c->info(json_encode(array_only($data, $fields)));
          // $c->info('branchid='.$branchid);
          
          $data['branchid'] = $branchid;
          
          // $c->info('data[branchid]='.$data['branchid']);
          // $c->info(json_encode(array_only($data, $fields)));
          // $c->info(json_encode($data));

          if ($this->ds->firstOrNewField(array_only($data, $fields), ['date', 'branchid']))
            $update++;
        
        }

          


          
        //   if ($from->copy()->subDay()->eq($vfpdate)) {
        //     $c->info('before month:'. $vfpdate->format('Y-m-d'));
        //     $c->info('trans_cnt:'. $data['trans_cnt']);
        //     if ($data['trans_cnt']<1)
        //       unset($data['trans_cnt']);
        //     $c->info('custcount:'. $data['custcount']);
        //     if ($data['custcount']<1) {
        //       unset($data['custcount']);
        //       unset($data['headspend']);
        //     }
        //   }

        
        // } else {
        //   $c->info('backlogDailySales $updates: '.$update);
        //   dbase_close($db);
        //   unset($db);
        //   return $update>0 ? $update:false;
        // }
      } // end:foreach $record_numbers
      
      $c->info('backlogDailySales $updates: '.$update);
      $c->info($update);
      //$this->logAction('end:loop:ds', '');
      dbase_close($db);
      unset($db);
      return $update>0 ? $update:false;
    
    } // end: file not exist
    return false;
  }

  private function checkSalesmtdDS($data, $branchid, $date, $c) {
    $d = DailySales::where(['date'=>$date->format('Y-m-d'), 'branchid'=>$branchid])->first();
    
    if (is_null($d)) {
      $d = DailySales::firstOrCreate(['date'=>$date->format('Y-m-d'), 'branchid'=>$branchid]);
    }
    $c->info($d->date->format('Y-m-d').' '.$d->custcount.' '.$d->trans_cnt);
    $arr = [];
    foreach ($data as $key => $value) {
      // $c->info($d->{$key});
      $x = $d->{$key};
      if ($x=='0' || is_null($x) || empty($x)) {
        $c->info('checkSalesmtdDS:'.$key.': '.$value);
        $arr[$key]=$value;
      } else {
        unset($arr[$key]);
      }
    }
   
    //if ($d->cospct<=0 || is_null($d->cospct) || empty($d->cospct)) {
      $arr['cospct']  = ($d->sales<=0) ? 0 : number_format(($d->cos/$d->sales)*100,2,'.','');
      $c->info('cospct: '.$arr['cospct']);
    //} else {
    //  $c->info('unset cospct');
    //  unset($arr['cospct']);
    //}

    return empty($arr) ? false: $arr;
  }

  public function backlogSalesmtd($branchid, Carbon $from, Carbon $to, $c) {

    $dbf_file = $this->extracted_path.DS.'SALESMTD.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);      
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $curr_date = null; 
      $update = 0;
      $ds = [];

      $ds['slsmtd_totgrs'] = 0;
      $ds['food_sales'] = 0;
      $ds['branchid'] = $branchid;

      for ($i=1; $i<=$recno; $i++) {
      //for ($i=$recno; $i>0; $i--) {
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }


        if ($vfpdate->gt($from->copy()->subDay()) && $vfpdate->lt($to->copy()->addDay())) {

          $data = $this->salesmtdCtrl->associateAttributes($row);

          if (is_null($curr_date)) {
            $curr_date = $vfpdate;
            $ds['opened_at'] = $data['ordtime'];
            $ds['closed_at'] = $data['ordtime'];
            $trans = 1;
            $curr_slipno = $data['cslipno'];
            
            $c->info('del: '.$curr_date->format('Y-m-d'));
            try {
              $this->salesmtdCtrl->deleteWhere(['branch_id'=>$branchid, 'orddate'=>$curr_date->format('Y-m-d')]);
              // $this->changeItem->deleteWhere(['branch_id'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]); // 02/14/2024
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
            
            //sleep(1);
          }

         

          if ($vfpdate->eq($curr_date)) {
            $ds['slsmtd_totgrs'] += $data['grsamt'];

            //$c->info($data['prodcat'].' '.$data['product'].' '.$data['cslipno']);
            if (strtolower($data['prodcat'])=='foods' && !strpos(strtolower($data['product']), 'pepsi')) {
              //$c->info('fc: '.$data['prodcat'].' '.$data['grsamt']);
              $ds['food_sales'] += $data['grsamt'];
            }

            if (c($ds['opened_at'])->gt(c($data['ordtime'])))
              $ds['opened_at'] = $data['ordtime'];

            if (c($ds['closed_at'])->lt(c($data['ordtime'])))
              $ds['closed_at'] = $data['ordtime'];
            
            if ($i==$recno) {
              //$c->info('save');
              /*
              $x = $this->checkSalesmtdDS(['trans_cnt'=>$trans], $branchid, $vfpdate, $c);
              if ($x)
                $ds = array_merge($ds, $x);              
              else 
                unset($ds['trans_cnt']);
              */
              
              $c->info('ds : '.$vfpdate->format('Y-m-d').' '.$ds['closed_at'].' '.$ds['slsmtd_totgrs'].' '.$i.' '.$trans);
              $c->info('fc: '.$curr_date->format('Y-m-d').' '.$ds['food_sales']);
              $ds['date'] = $vfpdate->format('Y-m-d');
              $this->ds->firstOrNewField($ds, ['date', 'branchid']);
              event(new \App\Events\Process\AggregatorDaily('change_item', $curr_date, $branchid));
            }

          } else {
            //$c->info('save');
            /*
            $x = $this->checkSalesmtdDS(['trans_cnt'=>$trans], $branchid, $curr_date, $c);
            if ($x)
              $ds = array_merge($ds, $x);
            else 
              unset($ds['trans_cnt']);
            */
            
            $c->info('ds : '.$curr_date->format('Y-m-d').' '.$ds['closed_at'].' '.$ds['slsmtd_totgrs'].' '.$i.' '.$trans);
            $c->info('fc: '.$curr_date->format('Y-m-d').' '.$ds['food_sales']);
            $ds['date'] = $curr_date->format('Y-m-d');
            $this->ds->firstOrNewField($ds, ['date', 'branchid']);
            event(new \App\Events\Process\AggregatorDaily('change_item', $curr_date, $branchid));

            $ds['slsmtd_totgrs'] = $data['grsamt'];
            $curr_date = $vfpdate;
            $trans=0;
            $ds['food_sales'] = 0;
            
            $ds['opened_at'] = $data['ordtime'];
            $ds['closed_at'] = $data['ordtime'];

            
            //$c->info($data['prodcat'].' '.$data['product'].' '.$data['cslipno']);
            if (strtolower($data['prodcat'])=='foods' && !strpos(strtolower($data['product']), 'pepsi')) {
              //$c->info('fc: '.$data['prodcat'].' '.$data['grsamt']);
              $ds['food_sales'] = $data['grsamt'];
            }
            
            $c->info('del: '.$curr_date->format('Y-m-d'));
            try {
              $this->salesmtdCtrl->deleteWhere(['branch_id'=>$branchid, 'orddate'=>$curr_date->format('Y-m-d')]);
              // $this->changeItem->deleteWhere(['branch_id'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]); // 02/15/2024
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
            
            //sleep(1);
          }

          if ($curr_slipno!=$data['cslipno']) {
            $trans++;
            $curr_slipno=$data['cslipno'];
          }
          //$c->info($trans.' '.$curr_slipno.' '.$data['cslipno']);
          

          //$c->info($trans.' '.$curr_slipno.' '.$data['cslipno']);
          $data['branch_id'] = $branchid;
            
          try {
            $this->salesmtdCtrl->create($data);
          } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('salesmtd: '.$e->getMessage());   
            return false;   
          }
            
          //$c->info($i.' '.$vfpdate->format('Y-m-d').'  '.$curr_date->format('Y-m-d').'  '.$data['grsamt'].'  '.$ds['slsmtd_totgrs'].' '.$data['ordtime']);
          $update++;
        }

      }

      $c->info($update);
      dbase_close($db);
      unset($db);
      return $update>0 ? $update:false;
    } 
    return false;  
  }

  /* whole month or till last backup process */
  public function backlogSalesmtdChangeItem($branchid, Carbon $from, Carbon $to, $c=NULL) {

    $dbf_file = $this->extracted_path.DS.'SALESMTD.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);      
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $curr_date = null; 
      $update = 0;
      $ctr = 0;


      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

       if ($vfpdate->gt($from->copy()->subDay()) && $vfpdate->lt($to->copy()->addDay())) {

          $x = trim($row['COMP4']);

          if (!empty($x)) {

            if (is_null($curr_date)) {
              $curr_date = $vfpdate;
              
              //$c->info('del: '.$curr_date->format('Y-m-d'));
              try {
                $this->changeItem->deleteWhere(['branch_id'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
              } catch(Exception $e) {
                dbase_close($db);
                throw $e;    
              }
            }

            if ($vfpdate->eq($curr_date)) {
              
            } else {

              $this->ds->firstOrNewField(['date'=>$curr_date->format('Y-m-d'), 'branchid'=> $branchid, 'change_item'=>$ctr], ['date', 'branchid']);

              $ctr = 0;
              $curr_date = $vfpdate;

              if (!is_null($c))
                $c->info('del: '.$curr_date->format('Y-m-d'));
              
              try {
                $this->changeItem->deleteWhere(['branch_id'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
              } catch(Exception $e) {
                dbase_close($db);
                throw $e;    
              }
            }

            if (!is_null($c))
              $c->info($x);

            $k = 0;
            $ci = [];
            $ci['date'] = $vfpdate->format('Y-m-d');
            $ci['cslipno'] = trim($row['CSLIPNO']);
            $ci['branch_id'] = $branchid;

            $len = explode(' ', $x);

            if (!is_null($c))
              $c->info('len - '.count($len));

            if (count($len)>1) {

              $ci['group'] = trim($row['COMP2']);
              
              foreach ($len as $key => $prod) {

                if (!empty($prod)) {
                  
                  if (!is_null($c))
                    $c->info($key.' = '.$prod);
              
                  preg_match_all('/(\d+(?:\.\d+)?)([A-Z0-9]+)/m', $x, $matches, PREG_SET_ORDER, 0);
                  
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
              }
              
              if (!is_null($c))
                $c->info(print_r($ci));

              try {
                $this->changeItem->verifyAndCreate($ci);
              } catch(Exception $e) {
                dbase_close($db);
                throw $e;    
              }

              $ctr++;
              $update++;
            } // end if (count($len))
          }

        } // end: $vfpdate->gte($from)
      }

      $this->ds->firstOrNewField(['date'=>$curr_date->format('Y-m-d'), 'branchid'=> $branchid, 'change_item'=>$ctr], ['date', 'branchid']);

      if (!is_null($c))
        $c->info($update);
      dbase_close($db);
      unset($db);
      return $update>0 ? $update:false;
    } 
    return false;  
  }

  public function backlogCharges($branchid, Carbon $from, Carbon $to, $c) {
    foreach ($this->getDays($from, $to) as $key => $date) {
      $c->info($key.' '.$date->format('Y-m-d'));
      $this->backlogChargeSigned($branchid, $date, $c);
    }
  }

  public function backlogChargeSigned($branchid, Carbon $date, $cmd) {

    try {
      $cmd->info('charge delete: '.$date->format('Y-m-d'));
      $this->charges->deleteWhere(['branch_id'=>$branchid, 'orddate'=>$date->format('Y-m-d')]);
    } catch(Exception $e) {
      throw new Exception('charges: '.$e->getMessage());    
    }

    $ds = [];
    $ds['bank_totchrg'] = 0;
    $ds['chrg_total']   = 0;
    $ds['chrg_csh']     = 0;
    $ds['chrg_chrg']    = 0;
    $ds['chrg_othr']    = 0;
    $ds['disc_totamt']  = 0;
    $ds['date']         = $date->format('Y-m-d');
    $ds['branchid']     = $branchid;

    $ds['grab']  = 0;
    $ds['grabc']  = 0;
    $ds['panda']  = 0;
    $ds['zap']  = 0;
    $ds['smo']  = 0;
    $ds['maya']  = 0;
    $ds['zap_delfee']  = 0;
    $ds['totdeliver']  = 0;
    $ds['tot_dine'] = 0;
    $ds['tot_togo'] = 0;
    $ds['tot_onlrid'] = 0;
    $ds['tot_osaletype'] = 0;
    $ds['zap_sales'] = 0;
    $ds['vat_xmpt'] = 0;
    $ds['pax_dine'] = 0;
    $ds['pax_togo'] = 0;
    $ds['pax_onlrid'] = 0;
    $ds['pax_osaletype'] = 0;
    $ds['trx_dine'] = 0;
    $ds['trx_togo'] = 0;
    $ds['trx_onlrid'] = 0;
    $ds['trx_osaletype'] = 0;


    try {
      $c = $this->backlogRawCharges($branchid, $date, $cmd);
    } catch(Exception $e) {
      throw new Exception('backlogChargeSigned:charges: '.$e->getMessage());    
    }

    try {
      $s = $this->backlogRawSigned($branchid, $date, $cmd);
    } catch(Exception $e) {
      throw new Exception('backlogChargeSigned:signed: '.$e->getMessage());    
    }

    $ds['bank_totchrg'] = $c['bank_totchrg']  + $s['bank_totchrg'];
    $ds['chrg_total']   = $c['chrg_total']    + $s['chrg_total'];
    $ds['chrg_csh']     = $c['chrg_csh']      + $s['chrg_csh'];
    $ds['chrg_chrg']    = $c['chrg_chrg']     + $s['chrg_chrg'];
    $ds['chrg_othr']    = $c['chrg_othr']     + $s['chrg_othr'];
    $ds['disc_totamt']  = $c['disc_totamt']   + $s['disc_totamt'];

    $ds['grab'] = $c['grab'] + $s['grab'];
    $ds['panda']  = $c['panda'] + $s['panda'];
    $ds['grabc'] = $c['grabc'] + $s['grabc'];
    $ds['zap'] = $c['zap'] + $s['zap'];
    $ds['smo']    = $c['smo'];
    $ds['maya']   = $c['maya'];
    $ds['zap_delfee'] = $c['zap_delfee'] + $s['zap_delfee'];
    $ds['totdeliver']  = $c['totdeliver'] + $s['totdeliver'];
    $ds['tot_dine'] =  $c['tot_dine'];
    $ds['tot_togo'] =  $c['tot_togo'];
    $ds['tot_onlrid'] =  $c['tot_onlrid'];
    $ds['tot_osaletype'] =  $c['tot_osaletype'];
    $ds['zap_sales'] = $c['zap_sales'] + $s['zap_sales'];
    $ds['vat_xmpt'] = $c['vat_xmpt'] + $s['vat_xmpt'];
    $ds['pax_dine'] =  $c['pax_dine'];
    $ds['pax_togo'] =  $c['pax_togo'];
    $ds['pax_onlrid'] =  $c['pax_onlrid'];
    $ds['pax_osaletype'] =  $c['pax_osaletype'];
    $ds['trx_dine'] =  $c['trx_dine'];
    $ds['trx_togo'] =  $c['trx_togo'];
    $ds['trx_onlrid'] =  $c['trx_onlrid'];
    $ds['trx_osaletype'] =  $c['trx_osaletype'];


    $cnt = $c['custcount'] + $s['custcount'];
    $hspend = ($cnt > 0) ? number_format($ds['chrg_total']/$cnt,2,'.','') : 0;
    $trans = $c['ctr'] + $s['ctr'];

    $x = $this->checkSalesmtdDS(['trans_cnt'=>$trans, 'custcount'=>$cnt, 'headspend'=>$hspend], $branchid, $date, $cmd);
    if ($x)
      $ds = array_merge($ds, $x);
    else {
      //unset($ds['custcount']);
      //unset($ds['headspend']);
      //unset($ds['trans_cnt']);
    }


    // update dailysales
    try {
      //$cmd->info(json_encode($ds));
      $this->ds->firstOrNewField($ds, ['date', 'branchid']);
    } catch(Exception $e) {
      //dbase_close($db);
      throw new Exception('charges:ds: '.$e->getMessage());    
    }
    //sleep(5);
    


    //$cmd->info(json_encode($ds));
    unset($ds);
    return false;
  }

  private function backlogRawCharges($branchid, $date, $c) {

    $dbf_file = $this->extracted_path.DS.'CHARGES.DBF';

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      
      $ds = [];
      $ds['bank_totchrg'] = 0;
      $ds['chrg_total'] = 0;
      $ds['chrg_csh']   = 0;
      $ds['chrg_chrg']  = 0;
      $ds['chrg_othr']  = 0;
      $ds['disc_totamt']  = 0;
      $ds['custcount']  = 0;

      $ds['panda']  = 0;
      $ds['grab']  = 0;
      $ds['grabc']  = 0;
      $ds['zap']  = 0;
      $ds['smo'] = 0;
      $ds['maya'] = 0;
      $ds['zap_delfee']  = 0;
      $ds['totdeliver']  = 0;
      $ds['tot_dine'] = 0;
      $ds['tot_togo'] = 0;
      $ds['tot_onlrid'] = 0;
      $ds['tot_osaletype'] = 0;
      $ds['zap_sales'] = 0;
      $ds['vat_xmpt'] = 0;
      $ds['pax_dine'] = 0;
      $ds['pax_togo'] = 0;
      $ds['pax_onlrid'] = 0;
      $ds['pax_osaletype'] = 0;
      $ds['trx_dine'] = 0;
      $ds['trx_togo'] = 0;
      $ds['trx_onlrid'] = 0;
      $ds['trx_osaletype'] = 0;

      for ($i=1; $i<=$recno; $i++) {
        
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          continue;
        }
        
        
        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
          $data = $this->charges->associateAttributes($row);
          $data['branch_id'] = $branchid;

          try {
            //$this->logAction($data['orddate'], ' create:charges');
            $this->charges->create($data);
            //$c->info('charge:save: '.$data['orddate'].' '.$data['ordtime'].' '.$data['cslipno'].' '.$data['chrg_type'].' '.$data['tblno'].' '.$data['chrg_grs']);
            $update++;
            } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('charges: '.$e->getMessage());  
            return false;  
          }

          switch (strtolower($data['terms'])) {
            case 'cash':
              $ds['chrg_csh'] += $data['tot_chrg'];
              break;
            case 'charge':
              $ds['chrg_chrg'] += $data['tot_chrg'];
              break;
            default:
              $ds['chrg_othr'] += $data['tot_chrg'];
              break;
          }

          switch (strtolower($data['saletype'])) {
            case 'dinein':
              $ds['tot_dine'] += $data['tot_chrg'];
              $ds['pax_dine'] += $data['custcount'];
              $ds['trx_dine'] ++;
              break;
            case 'tkeout':
              $ds['tot_togo'] += $data['tot_chrg'];
              $ds['pax_togo'] += $data['custcount'];
              $ds['trx_togo'] ++;
              break;
            case 'onlrid':
            case 'calwed':
            case 'onlwed':
              $ds['tot_onlrid'] += $data['tot_chrg'];
              $ds['pax_onlrid'] += $data['custcount'];
              $ds['trx_onlrid'] ++;
              if ((starts_with(stl($data['tblno']), 'z') || starts_with(stl($data['tblno']), 'p') || starts_with(stl($data['tblno']), 'g') || starts_with(stl($data['tblno']), 's'))
                && (strlen($data['tblno'])>4) && (strtolower($data['terms'])!='charge')) {
                $ds['totdeliver'] += $data['tot_chrg'];
              }
              break;
            default:
              $ds['tot_osaletype'] += $data['tot_chrg'];
              $ds['pax_osaletype'] += $data['custcount'];
              $ds['trx_osaletype'] ++;
              break;
          }
          
          $ds['chrg_total']   += $data['tot_chrg'];
          $ds['bank_totchrg'] += $data['bank_chrg'];
          $ds['disc_totamt']  += $data['disc_amt'];
          $ds['custcount']    += $data['custcount'];
          $ds['vat_xmpt']    += $data['vat_xmpt'];

          if (strtolower($data['terms'])=='charge') {
            switch (strtolower($data['chrg_type'])) {
              case 'panda':
                $ds['panda'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grab':
                $ds['grab'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grabc':
                $ds['grabc'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'maya':
                $ds['maya'] += $data['tot_chrg'];
                break;
              case 'smo-del':
                $ds['smo'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'smo-pu':
                $ds['smo'] += $data['tot_chrg'];
                break;
              case 'zap':
              case 'cod-panda':
              case 'cod-grab':
              case 'cod-3p':
              case 'zap-panda':
              case 'zap-grab':
              case 'zap-3p':
                $ds['zap'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                $ds['zap_delfee'] += $data['delivery_fee'];
                break;
            }
          }

          if (starts_with(strtolower($data['tblno']), 'z') && (substr($data['tblno'], -4) > 0))
            $ds['zap_sales'] += $data['tot_chrg'];

        }
      }
      $ds['ctr'] = $update;
      
      dbase_close($db);
    }
    return $ds;
  }

  public function backlogRawSigned($branchid, $date, $c) {

    $dbf_file = $this->extracted_path.DS.'SIGNED.DBF';

    if (file_exists($dbf_file)) {
      //$this->logAction('posting', 'post:charges');
      $db = dbase_open($dbf_file, 0);
      
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      
      $ds = [];
      $ds['bank_totchrg'] = 0;
      $ds['chrg_total'] = 0;
      $ds['chrg_csh']   = 0;
      $ds['chrg_chrg']  = 0;
      $ds['chrg_othr']  = 0;
      $ds['disc_totamt']  = 0;
      $ds['custcount']  = 0;

      $ds['panda']  = 0;
      $ds['grab']  = 0;
      $ds['grabc']  = 0;
      $ds['zap']  = 0;
      $ds['zap_delfee']  = 0;
      $ds['totdeliver']  = 0;
      $ds['zap_sales']  = 0;
      $ds['vat_xmpt']  = 0;

      for ($i=1; $i<=$recno; $i++) {
        
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          continue;
        }
        
        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {
          $data = $this->charges->associateAttributes($row);
          $data['branch_id'] = $branchid;

          try {
            //$this->logAction($data['orddate'], ' create:charges');
            $this->charges->create($data);
            //$c->info('signed:save: '.$data['orddate'].' '.$data['ordtime'].' '.$data['cslipno'].' '.$data['chrg_type'].' '.$data['tblno'].' '.$data['chrg_grs']);
            $update++;
            } catch(Exception $e) {
            dbase_close($db);
            throw new Exception('backlogRawSigned: '.$e->getMessage());  
            return false;  
          }

          switch (strtolower($data['terms'])) {
            case 'cash':
              $ds['chrg_csh'] += $data['tot_chrg'];
              break;
            case 'charge':
              $ds['chrg_chrg'] += $data['tot_chrg'];
              break;
            default:
              $ds['chrg_othr'] += $data['tot_chrg'];
              break;
          }

          $ds['chrg_total'] += $data['tot_chrg'];
          $ds['bank_totchrg'] += $data['bank_chrg'];
          $ds['disc_totamt']  += $data['disc_amt'];
          $ds['custcount']    += $data['custcount'];
          $ds['vat_xmpt']    += $data['vat_xmpt'];

          if (strtolower($data['terms'])=='charge') {
            switch (strtolower($data['chrg_type'])) {
              case 'panda':
                $ds['panda'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grab':
                $ds['grab'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'grabc':
                $ds['grabc'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                break;
              case 'zap':
              case 'cod-panda':
              case 'cod-grab':
              case 'cod-3p':
              case 'zap-panda':
              case 'zap-grab':
              case 'zap-3p':
                $ds['zap'] += $data['tot_chrg'];
                $ds['totdeliver'] += $data['tot_chrg'];
                $ds['zap_delfee'] += $data['delivery_fee'];
                break;
            }
          }

          if (starts_with(strtolower($data['tblno']), 'z') && (substr($data['tblno'], -4) > 0))
            $ds['zap_sales'] += $data['tot_chrg'];

        }
      }
      $ds['ctr'] = $update;

      dbase_close($db);
    }
    return $ds;
  }

  public function backlogPurchased($branchid, Carbon $from, Carbon $to, $c) {

    $dbf_file = $this->extracted_path.DS.'PURCHASE.DBF';
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $trans = 0;
      $curr_date = null;
      $ds = [];

      $ds['purchcost'] = 0;
      $ds['cos'] = 0;
      $ds['opex'] = 0;
      $ds['branchid'] = $branchid;


      try {
        $c->info('delete range: '.$from->format('Y-m-d').'-'.$to->format('Y-m-d'));
        
        // \App\Models\Purchase2::whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])->delete();
        \App\Models\Purchase2::where('branchid', $branchid)->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])->delete();

      } catch(Exception $e) {
        dbase_close($db);
        throw $e;    
      }


      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);

        try {
          $data = $this->purchase2->associateAttributes($row);
        } catch(Exception $e) {
          // log on error
          continue;
        }


        $data['branchid'] = $branchid;

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }
        
        if ($vfpdate->gt($from->copy()->subDay()) && $vfpdate->lt($to->copy()->addDay())) {

          if (is_null($curr_date)) {
            $curr_date = $vfpdate;
            $trans = 1;

          try {
            $this->purchase->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }

            try {
              $c->info('del: '.$curr_date->format('Y-m-d'));
              $this->purchase2->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
          }


          if ($curr_date->eq($vfpdate)) {

            $trans++;
            //$ds['purchcost'] += $data['tcost'];
            
            //if (in_array(substr($data['supno'], 0, 2), $this->expense_array))
            //  $ds['cos'] += $data['tcost'];
            //if (!in_array(substr($data['supno'], 0, 2), $this->expense_array) && !in_array(substr($data['supno'], 0, 2), $this->non_cos_array))
            //  $ds['opex'] += $data['tcost'];

            if ($i==$recno) {
              $c->info('ds:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['purchcost'].' '.$ds['cos']);
              $ds['date'] = $curr_date->format('Y-m-d');
              //$this->ds->firstOrNewField($ds, ['date', 'branchid']); /////////////////////////////////////////////////////////////////
            }

          } else {
            
            $c->info('ds:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['purchcost'].' '.$ds['cos']); 
            $ds['date'] = $curr_date->format('Y-m-d');  
            //$this->ds->firstOrNewField($ds, ['date', 'branchid']); ////////////////////////////////////////////////////////////////////
            
            $curr_date = $vfpdate;          
            $trans=1;
            //$ds['purchcost'] = $data['tcost'];
            //$ds['cos']=0;
            //$ds['opex']=0;
            
            //if (in_array(substr($data['supno'], 0, 2), $this->expense_array))
            //  $ds['cos'] = $data['tcost'];
            //if (!in_array(substr($data['supno'], 0, 2), $this->expense_array) && !in_array(substr($data['supno'], 0, 2), $this->non_cos_array))
            //  $ds['opex'] = $data['tcost'];

            try {
              $this->purchase->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }

            try {
              $c->info('del: '.$curr_date->format('Y-m-d'));
              $this->purchase2->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
          }

          // $c->info($trans.' '.$vfpdate->format('Y-m-d').' '.$curr_date->format('Y-m-d').' '.$data['comp'].' '.$data['tcost']);
          
          try {
            $this->purchase2->verifyAndCreate($data);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }

        } // end: $vfpdate->gte($from)
      } // end: for

      dbase_close($db);
      unset($db);
      return $update>0 ? $update:false;
    }
    return false;
  }

  public function backlogPurchased2($branchid, Carbon $from, Carbon $to, $c) {

    $dbf_file = $this->extracted_path.DS.'PURCHASE.DBF';
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $trans = 0;
      $curr_date = null;
      $ds = [];

      $ds['purchcost'] = 0;
      $ds['cos'] = 0;
      $ds['opex'] = 0;
      $ds['branchid'] = $branchid;


      try {
        $c->info('delete range: '.$from->format('Y-m-d').'-'.$to->format('Y-m-d'));
        
        // \App\Models\Purchase2::whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])->delete();
        \App\Models\Purchase2::where('branchid', $branchid)->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])->delete();

      } catch(Exception $e) {
        dbase_close($db);
        throw $e;    
      }


      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);

        try {
          $data = $this->purchase2->associateAttributes($row);
        } catch(Exception $e) {
          // log on error
          continue;
        }

        $data['branchid'] = $branchid;

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

        if (is_null($curr_date)) {
          $curr_date = $vfpdate;
          $trans = 1;

        try {
          $this->purchase->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
        } catch(Exception $e) {
          dbase_close($db);
          throw $e;    
        }

          try {
            $c->info('del: '.$curr_date->format('Y-m-d'));
            $this->purchase2->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }
        }


        if ($curr_date->eq($vfpdate)) {

          $trans++;
          $ds['purchcost'] += $data['tcost'];
          
          // $c->info($data['supno'].' '.substr($data['supno'], 0, 2));
          if (in_array(substr($data['supno'], 0, 2), $this->expense_array))
           $ds['cos'] += $data['tcost'];
          if (!in_array(substr($data['supno'], 0, 2), $this->expense_array) && !in_array(substr($data['supno'], 0, 2), $this->non_cos_array))
           $ds['opex'] += $data['tcost'];

          if ($i==$recno) {
            $c->info('ds:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['purchcost'].' '.$ds['cos']);
            $ds['date'] = $curr_date->format('Y-m-d');
            $this->ds->firstOrNewField($ds, ['date', 'branchid']); /////////////////////////////////////////////////////////////////
          }

        } else {
          
          $c->info('ds:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['purchcost'].' '.$ds['cos']); 
          $ds['date'] = $curr_date->format('Y-m-d');  
          $this->ds->firstOrNewField($ds, ['date', 'branchid']); ////////////////////////////////////////////////////////////////////
          
          $curr_date = $vfpdate;          
          $trans=1;
          $ds['purchcost'] = $data['tcost'];
          $ds['cos']=0;
          $ds['opex']=0;
          
          // $c->info($data['supno'].' '.substr($data['supno'], 0, 2));
          if (in_array(substr($data['supno'], 0, 2), $this->expense_array))
           $ds['cos'] = $data['tcost'];
          if (!in_array(substr($data['supno'], 0, 2), $this->expense_array) && !in_array(substr($data['supno'], 0, 2), $this->non_cos_array))
           $ds['opex'] = $data['tcost'];

          try {
            $this->purchase->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }

          try {
            $c->info('del: '.$curr_date->format('Y-m-d'));
            $this->purchase2->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }
        }

        $c->info($trans.' '.$vfpdate->format('Y-m-d').' '.$curr_date->format('Y-m-d').' '.$data['comp'].' '.$data['tcost']);
        
        try {
          $this->purchase2->verifyAndCreate($data);
        } catch(Exception $e) {
          dbase_close($db);
          throw $e;    
        }

      }

      dbase_close($db);
      unset($db);
      return $update>0 ? $update:false;
    }
    return false;
  }

  public function backlogTransfer($branchid, Carbon $from, Carbon $to, $c) {

    $dbf_file = $this->extracted_path.DS.'TRANSFER.DBF';
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $trans = 0;
      $curr_date = null;
      $ds = [];

      $ds['transcost'] = 0;
      $ds['transcos'] = 0;
      $ds['transncos'] = 0;
      $ds['emp_meal'] = 0;
      $ds['branchid'] = $branchid;

      $brc = $this->getBackupCode();

      $branchcode = $brc['code'];

       $c->info('branchcode: '.$branchcode);

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $data = $this->transfer->associateAttributes($row);
        $data['branchid'] = $branchid;

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

        if ($vfpdate->gt($from->copy()->subDay()) && $vfpdate->lt($to->copy()->addDay())) {


          if (is_null($curr_date)) {
            $curr_date = $vfpdate;
            $trans = 1;

            try {
              $c->info('del: '.$curr_date->format('Y-m-d'));
              $this->transfer->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
          }


          if ($curr_date->eq($vfpdate)) {

            $trans++;
            //if ($data['tcost']>0)
              $ds['transcost'] += $data['tcost'];
            
            $c->info('tcost:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['transcost'].' tcost:'.$data['tcost']);

            //if (in_array(substr($data['supno'], 0, 2), $this->expense_array) && $data['tcost']>0)
            //  $ds['transcos'] += $data['tcost'];

            //if (in_array(substr($data['supno'], 0, 2), $this->expense_array) && $data['tcost']>0) {
            if (in_array(substr($data['supno'], 0, 2), $this->expense_array)) {
              $ds['transcos'] += $data['tcost'];
              if (strtolower(substr($data['supno'], 2, 3))==strtolower($branchcode))
                $ds['emp_meal'] += $data['tcost'];
            } 
            //if (in_array(substr($data['supno'], 0, 2), $this->non_cos_array) && $data['tcost']>0) 
            if (in_array(substr($data['supno'], 0, 2), $this->non_cos_array)) 
              $ds['transncos'] += $data['tcost'];
            
            if ($i==$recno) {
              $c->info('ds:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['transcost'].' '.$ds['transcos']);
              $ds['date'] = $curr_date->format('Y-m-d');
              $this->ds->firstOrNewField($ds, ['date', 'branchid']);
              // push emp meal on purchase
              event('transfer.empmeal', ['data'=>['branch_id'=>$branchid, 'date'=>$curr_date, 'suppliercode'=>$branchcode]]);
            }

          } else {
            
            $c->info('ds:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['transcost'].' '.$ds['transcos']); 
            $ds['date'] = $curr_date->format('Y-m-d');  
            $this->ds->firstOrNewField($ds, ['date', 'branchid']);
            // push emp meal on purchase
            event('transfer.empmeal', ['data'=>['branch_id'=>$branchid, 'date'=>$curr_date, 'suppliercode'=>$branchcode]]);
            
            $curr_date = $vfpdate;          
            $trans=1;
            /*
            if ($data['tcost']>0)
              $ds['transcost'] = $data['tcost'];
            else 
              $ds['transcost'] = 0;
            */
            $ds['transcost'] = $data['tcost'];

            $c->info('tcost:  '.$curr_date->format('Y-m-d').' '.$trans.' '. $ds['transcost'].' tcost:'.$data['tcost']);
            $ds['transcos'] = 0;
            $ds['transncos'] = 0;
            $ds['emp_meal'] = 0;

            //if (in_array(substr($data['supno'], 0, 2), $this->expense_array) && $data['tcost']>0)
            //  $ds['transcos'] = $data['tcost'];
            //if (in_array(substr($data['supno'], 0, 2), $this->expense_array) && $data['tcost']>0) {
            if (in_array(substr($data['supno'], 0, 2), $this->expense_array)) {
              $ds['transcos'] = $data['tcost'];
              if (strtolower(substr($data['supno'], 2, 3))==strtolower($branchcode))
                $ds['emp_meal'] = $data['tcost'];
            } 
            //if (in_array(substr($data['supno'], 0, 2), $this->non_cos_array) && $data['tcost']>0) 
            if (in_array(substr($data['supno'], 0, 2), $this->non_cos_array)) 
              $ds['transncos'] = $data['tcost'];

            
            try {
              $c->info('del: '.$curr_date->format('Y-m-d'));
              $this->transfer->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
            } catch(Exception $e) {
              dbase_close($db);
              throw $e;    
            }
          }

          //$c->info($trans.' '.$vfpdate->format('Y-m-d').' '.$curr_date->format('Y-m-d').' '.$data['comp'].' '.$data['tcost']);

          
          $data['to'] = $this->deliveredTo(substr($data['supno'], 2, 3), $data);
          try {
            //$c->info('to: '. substr($data['supno'], 2, 3).' '.$data['to']);
            $this->transfer->verifyAndCreate($data);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }

        } // end: $vfpdate->gte($from)
      } //end: for

      dbase_close($db);
      unset($db);
      return $update>0 ? $update:false;
    }
    return false;
  }

  private function deliveredTo($code, $data) {
    $branch = new BranchRepository;
    $supplier = new SupplierRepository;

    if ($code=='711') {
      return '971077BCA54611E5955600FF59FBB323';
    }

    $br = $branch->findWhere(['code'=>$code]);
    if (count($br)>0) {
      return $br->first()->id;
    } else {
      $su = $supplier->verifyAndCreate(array_only($data, ['supno', 'supname', 'branchid', 'tin', 'terms']));
      //$su = $supplier->findWhere(['code'=>$code]);
      //if (count($su)>0) 
      if (!is_null($su)) {
        //return $su->first()->id;
        return $su->id;
      } else {
        return $code;
      }
    }
  }

  public function backlogCashAudit2($branchid, Carbon $from, Carbon $to, $c=NULL) {

    $kitlogImporter = $this->dbfImporter->invoke('cash_audit');
    $cnt = $kitlogImporter->importByDr($branchid, $from, $to, $this->extracted_path, $c);

    return $cnt;
  }

  public function backlogKitlog($branchid, Carbon $from, Carbon $to, $c=NULL) {

    $kitlogImporter = $this->dbfImporter->invoke('kitlog');
    
    $gt = $cnt = 0;
    foreach (dateInterval($from, $to) as $key => $date) {
      if (!is_null($c))
       $c->info($date);
      
      $cnt = $kitlogImporter->import($branchid, $date, $this->extracted_path, $c);
      
      if ($cnt>0) 
        $this->ds->firstOrNewField(['date'=>$date->format('Y-m-d'), 'branchid'=> $branchid, 'kitlog'=>1], ['date', 'branchid']);

      event(new \App\Events\Process\AggregatorKitlog('day_kitlog_food', $date, $branchid));
      event(new \App\Events\Process\AggregatorKitlog('day_kitlog_area', $date, $branchid));
     
    
      $gt += $cnt;
    }

    if (!is_null($c))
      $c->info('Kitlog Transactions: '.$gt);

    return $gt;
  }

  public function processChangeItem($branchid, Carbon $from, Carbon $to, $c=NULL) {

    $importer = $this->dbfImporter->invoke('change_item');
    
    $gt = $cnt = 0;
    foreach (dateInterval($from, $to) as $key => $date) {
      if (!is_null($c))
       $c->info($date);
      
      $cnt = $importer->import($branchid, $date, $c);
      
      // if ($cnt>0) 
        // $this->ds->firstOrNewField(['date'=>$date->format('Y-m-d'), 'branchid'=> $branchid, 'kitlog'=>1], ['date', 'branchid']);

    
      $gt += $cnt;
    }

    if (!is_null($c))
      $c->info('Change Item Transactions: '.$gt);

    return $gt;

  }



  
###################################################################################################################################
############## endfor App\Command\Backlog\MonthDaily ##############################################################################
###################################################################################################################################
    
}