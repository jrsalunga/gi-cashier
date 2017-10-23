<?php namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Rmis\Product;
use App\Models\Rmis\Invdtl as InvdtlModel;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use App\Http\Controllers\SalesmtdController as SalesmtdCtrl;
use App\Repositories\Rmis\Invdtl;
use App\Repositories\Rmis\Orpaydtl;

class EndOfDay extends Command
{
/**
 * The name and signature of the console command.
 *
 * @var string
 */
 protected $signature = 'pos:eod 
                        {date : YYYY-MM-DD}
                        {--brcode=EGC : branch code}';

/**
 * The console command description.
 *
 * @var string
 */
protected $description = 'Generate eod';

/**
 * Execute the console command.
 *
 * @return mixed
 */

protected $temp_path;
protected $date;
protected $invdtl;
protected $orpaydtl;
protected $save_cancelled = true;
protected $payment_type = [1=>'CASH', 2=>'CHRG', 3=>'GCRT', 4=>'SIGN'];
protected $payment_breakdown = [];
protected $gross = 0;
protected $prodtype_breakdown = [];

public function __construct(Invdtl $invdtl, Orpaydtl $orpaydtl) {
  parent::__construct();
  $this->invdtl = $invdtl;
  $this->orpaydtl = $orpaydtl;
}

public function handle()
{
    /*
    $br = Branch::where('code', strtoupper($this->option('brcode')))->first();
    if (!$br) {
        $this->error('Invalid Branch Code.');
      exit;
    }
    */
    

    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->error('Invalid date.');
      exit;
    }

    $date = Carbon::parse($date);
    if ($date->gte(c())) {
      $this->error('Invalid backup date. Too advance to backup.');
      exit;
    } 
    $this->date = $date;  


    $this->info('Generating backup...');


    if ($this->set_temp_path()) {
    //if ($this->set_temp_path($br->code)) {
      
      $this->info('');
      $this->info($this->temp_path);

      //$this->update_products();

      $this->info('');
      $this->info("\tPROCESSING SALESMTD");
      
      $this->salesmtd($date);

      $this->info('');
      $this->info('*******************************************');
      $this->info("\tPROCESSING PAYMENTS");
      $this->info('');
      $this->charges($date);

     

    }
  }

  private function get_payment_type($key) {
    return array_key_exists($key, $this->payment_type)
      ? $this->payment_type[$key]
      : 'OTHR';
  }


  private function charges(Carbon $date) {

    $c = $this->createDBF('CHRG'.$date->format('md'), $this->charges_fields());
    $s = $this->createDBF('SIGN'.$date->format('md'), $this->charges_fields());

    $dbf_c = dbase_open($c, 2);
    $dbf_s = dbase_open($s, 2);

    if ($dbf_c && $dbf_s) {

      $orpaydtls = $this->orpaydtl
        ->skipCache()
        ->whereDate($date)
        ->with([
          'invhdr'=>function($q) {
            $q->select(['refno', 'id'])
              ->with(['scinfos'=>function($q){
                $q->where('cancelled', 0);
              }]);
          }
        ])
        ->all();

      $tot = 0;
      $ptype = null;
      $terminals = [];
      
      $this->info('+---------------------------------------------+');
      $this->info(
            str_pad('#',4,' ',STR_PAD_LEFT).' '.
            str_pad('TYPE',4,' ',STR_PAD_LEFT).' '.
            str_pad('REFNO',4,' ',STR_PAD_LEFT).' '.
            str_pad('TENDERED',11,' ',STR_PAD_LEFT).' '.
            str_pad('AMT PAID',10,' ',STR_PAD_LEFT).' '.
            str_pad('CHANGE',10,' ',STR_PAD_LEFT).' '.
            str_pad('MID',5,' ',STR_PAD_LEFT)
          );
      $this->info('+---------------------------------------------+');
      
      foreach ($orpaydtls as $key => $orpaydtl) {
        $true_charge = 0;
        if (in_array($orpaydtl->paytype, [1,2])) {
          $this->add_record($dbf_c,  $this->setOrpaydtl($orpaydtl));
          if ($orpaydtl->paytype=='1') {
            $ptype = 'CASH';
            $true_charge = $orpaydtl->amounts-$orpaydtl->totchange;
            $tot += $true_charge;
          } else {
            $ptype = 'CHGR'; 
            $true_charge = $orpaydtl->amounts;
            $tot += $orpaydtl->amounts;
          }
        }

        if ($orpaydtl->paytype=='4') {
          $this->add_record($dbf_s,  $this->setOrpaydtl($orpaydtl));
          $ptype = 'SIGN';
          $true_charge = $orpaydtl->amounts;
          $tot += $orpaydtl->amounts; 
        }

        if (in_array($orpaydtl->paytype, [1,2,4])) {

          $this->info(
            str_pad(($key+1),4,' ',STR_PAD_LEFT).' '.
            $ptype.' '.
            substr($orpaydtl->invrefno,4).' '.
            str_pad(number_format($orpaydtl->amounts,2),10,' ',STR_PAD_LEFT).' '.
            str_pad(number_format($true_charge,2),10,' ',STR_PAD_LEFT).' '.
            str_pad(number_format($orpaydtl->amounts-$true_charge,2),10,' ',STR_PAD_LEFT).' '.
            $orpaydtl->terminalid
          );

          if (array_key_exists($orpaydtl->terminalid, $terminals))
            $terminals[$orpaydtl->terminalid] += $true_charge;
           else
            $terminals[$orpaydtl->terminalid] = $true_charge;
            
        }

        $type = $this->get_payment_type($orpaydtl->paytype);
        if (array_key_exists($type, $this->payment_breakdown))
          $this->payment_breakdown[$type] += $true_charge;
        else
          $this->payment_breakdown[$type] = $true_charge;

      }
      
      $this->close_dbf($dbf_c);
      $this->close_dbf($dbf_s);

      $this->info('+---------------------------------------------+');
      $this->info('TOTAL: '.number_format($tot,2));
      $this->info('+---------------------------------------------+');
      $this->info(' ');

      $tot_ptype = 0;
      $this->info('*** Payment Type ***');
      foreach ($this->payment_breakdown as $key => $value) {
        $this->info($key.' '.str_pad(number_format($value,2),12,' ',STR_PAD_LEFT));
        $tot_ptype += $value;
      }
      $this->info('--------------------');
      $this->info(str_pad(number_format($tot_ptype,2),17,' ',STR_PAD_LEFT));

      $tot_terminal = 0;
      $this->info(' ');
      $this->info('*** Terminal ***');
      foreach ($terminals as $key => $t) {
        $this->info($key.' '.str_pad(number_format($t,2),10,' ',STR_PAD_LEFT));
        $tot_terminal += $t;
      }
      $this->info('----------------');
      $this->info(str_pad(number_format($tot_terminal,2),14,' ',STR_PAD_LEFT));

    }
  }

  private function set_temp_path($dir=null) {

    $root_path = is_null($dir) 
      //? storage_path().DS.'eod'.DS.'_tmp'.DS.$this->date->format('Ymd')
      ? 'C:\\GI_GLO'
      : storage_path().DS.'eod'.DS.$dir.DS.$this->date->format('Ymd');

    if(!is_dir($root_path))
      mkdir($root_path, 0775, true);

    $this->temp_path = $root_path;

    return true;
  }


  private function createDBF($filename=null, $fields=null) {

    if (is_null($filename)) 
      throw new \Exception('No filename set on createDBF');

    if (is_null($fields)) 
      throw new \Exception('No fields set on createDBF');

    $dbf = $this->temp_path.DS.$filename.'.DBF';
   
    if (!dbase_create($dbf, $fields)) 
      throw new \Exception('Unable to create DBF');
    
    return $dbf;
  }

  private function salesmtd(Carbon $date) {

    $path = $this->createDBF('SALE'.$date->format('md'), $this->salesmtd_fields());

    $dbf = dbase_open($path, 2);

    if ($dbf) {

      $invdtls = $this->invdtl
        ->skipCache()
        ->whereDate($date)
        ->with([
          'invhdr'=>function($q) {
            $q->select(['refno', 'date', 'tableno', 'timestart','uidcreate','pax','id']);
          }, 
          'product'=>function($q) {
            $q->select(['code', 'shortdesc', 'prodcatid', 'sectionid', 'id'])
              ->with('prodcat')
              ->with(['menuprod'=>function($q){
                $q->with('menucat')
                  ->orderBy('seqno');
            }]);
          }
        ])
        ->all();

      //$this->info(count($invdtls));

      $tot = 0;
      $last_clspno = null;
      $flag = false;
      foreach ($invdtls as $key => $invdtl) {

        if ($last_clspno==$invdtl->invhdr->refno) {
          $flag = false;
        } else {
          $this->line(' ');
          $this->line('==========================================');
          $this->line("\t".substr($invdtl->invhdr->refno,4));
          $this->line('==========================================');
          $last_clspno=$invdtl->invhdr->refno;
          $flag = true;
        }

        if ($invdtl->cancelled)
          $this->info((-1*abs($invdtl->qty)+0).' '.str_pad($invdtl->product->shortdesc,25,' ')." ".str_pad(number_format($invdtl->unitprice,2),8,' ',STR_PAD_LEFT)."\t".str_pad(number_format(-1*abs($invdtl->amount),2),8,' ',STR_PAD_LEFT));
        else
          $this->info(($invdtl->qty+0).' '.str_pad($invdtl->product->shortdesc,25,' ')." ".str_pad(number_format($invdtl->unitprice,2),8,' ',STR_PAD_LEFT)."\t".str_pad(number_format($invdtl->amount,2),8,' ',STR_PAD_LEFT));
                  
        if ($this->is_groupies($invdtl)) {
          
          $invdtl->product->load(['combos'=>function($q){
              $q->with('product')
                ->orderBy('seqno');
            }]);
          
          foreach ($invdtl->product->combos as $key => $combo) {
            $this->add_record($dbf, $this->setToArray($flag, $key, $invdtl, $combo, 'combo'));
            if($invdtl->cancelled)
              $this->add_record($dbf, $this->setToArray($flag, $key, $invdtl, $combo, 'combo', true));
          }
        } else {
          $this->add_record($dbf, $this->setToArray($flag, 0, $invdtl, null, 'invdtl'));
          if($invdtl->cancelled)
            $this->add_record($dbf, $this->setToArray($flag, 0, $invdtl, null, 'invdtl', true));
        }

        $tot += $invdtl->amount;
        
      } // end: foreach $invdtls

      $this->info('+-----------------------------------------------+');
      $this->comment('GROSS: '.number_format($tot, 2));
      $this->info('+-----------------------------------------------+');
      $this->info(' ');

      $tot_prodcat = 0;
      $this->info(' ');
      $this->info('*** Product Category ***');
      foreach ($this->prodtype_breakdown as $key => $value) {
        $this->info(str_pad(substr($key,0,15),15,' ').': '.str_pad(number_format($value,2),13,' ',STR_PAD_LEFT));
        $tot_prodcat += $value;
      }
      $this->info('+-----------------------------+');
      $this->info(str_pad(number_format($tot_prodcat,2),30,' ',STR_PAD_LEFT));

      $this->close_dbf($dbf);
    }
  }

  private function setOrpaydtl($orpaydtl) {

    $cardno = '';  
    $cardtyp = '';
    $lastpd = '';
    $cusaddr1 = '';
    $cusaddr2 = '';
    $cusno = '';
    $cusname = '';
    $pdamt = number_format(0,2);
    $bnkchrg = number_format(0,2);
    $terms = '';
    //$true_charge = number_format(0,2);

    if ($orpaydtl->paytype=='1') { //cash
      $cusno = $orpaydtl->tableno;
      $pdamt = $orpaydtl->amounts;
      $cusname = 'CASH';
      $terms = 'CASH';
      $true_charge = $orpaydtl->amounts-$orpaydtl->totchange;
      //$this->info('totpayline:'.$orpaydtl->totpayline);
      if ($orpaydtl->totpayline<=1 || $orpaydtl->ismulti>1)
        $lastpd = $orpaydtl->date->format('Ymd');
      if (count($orpaydtl->invhdr->scinfos)>0 && $orpaydtl->scpax>0) {
        $cusaddr1 = 'SC'.$orpaydtl->scpax.' '.$orpaydtl->invhdr->scinfos[0]->fullname;  
        $cusaddr2 = $orpaydtl->invhdr->scinfos[0]->issueplace.' '.$orpaydtl->invhdr->scinfos[0]->issuedate->format('mdy');
      }
    } 

    if ($orpaydtl->paytype=='2') {//charge

      $orpaydtl->load('bankcard');
      $cusno = $orpaydtl->tableno;
      $cusname = $orpaydtl->bankcard->descriptor;
      $bnkchrg = number_format(($orpaydtl->amounts*$orpaydtl->bankrate)/100,2);
      $terms = 'CHARGE';
      $true_charge = $orpaydtl->amounts;
      $cardno = $orpaydtl->approval.' '.$orpaydtl->refno;  
      $cardtyp = 'VISA';
      $cusaddr1 = $orpaydtl->fullname;
      $cusaddr2 = 'VISA';
    } 

    if ($orpaydtl->paytype=='4') {// signed
      $cusno = $orpaydtl->refno;
      $cusname = $orpaydtl->fullname;
      $terms = 'SIGNED';
      $true_charge = $orpaydtl->amounts;
    }



    return [
      substr($orpaydtl->invrefno,4), //CSLIPNO 
      $orpaydtl->date->format('Ymd'), //ORDDATE 
      $orpaydtl->timestop.':00', //ORDTIME 
      $cusno, //CUSNO 
      $cusname, //CUSNAME 
      $orpaydtl->bankrate, //CHARGPCT  
      $orpaydtl->subtotal, //GRSCHRG 
      number_format(0,2), //PROMO_PCT 
      number_format(0,2), //number_format($orpaydtl->promoamt,2), //PROMO_AMT 
      $orpaydtl->pax, //SR_TCUST  
      $orpaydtl->scpax, //SR_BODY 
      number_format($orpaydtl->scdisc+$orpaydtl->scdisc2,2,'.',''), //SR_DISC 
      $orpaydtl->vatamount, //VAT 
      $orpaydtl->svcamount, //SERVCHRG  
      //$orpaydtl->discamount, //OTHDISC 
      number_format($orpaydtl->discamount,2,'.',''), //OTHDISC 
      number_format(0,2), //UDISC 
      $bnkchrg, //BANKCHARG 
      number_format($true_charge,2,'.',''), //$orpaydtl->amount, //TOTCHRG 
      $pdamt, //PDAMT 
      '', //PMTDISC 
      number_format($orpaydtl->amounts-$pdamt,2,'.',''), //BALANCE 
      $terms, //TERMS 
      $cardno, //CARDNO  
      $cardtyp, //CARDTYP 
      $lastpd, //LASTPD  
      $orpaydtl->uidcreate, //REMARKS 
      $cusaddr1, //CUSADDR1  
      $cusaddr2, //CUSADDR2  
      '', //CUSTEL  
      '', //CUSFAX  
      '', //CUSCONT 
      '', //TCASH 
      '', //TCHARGE 
      '', //TSIGNED 
      '', //AGE 
      number_format($orpaydtl->vatxmpt,2,'.',''), //VAT_XMPT  
      '', //FILLER1 
      '', //FILLER2 
      '', //DIS_PROM  
      '', //DIS_UDISC 
      number_format($orpaydtl->scdisc+$orpaydtl->scdisc2,2,'.',''), //DIS_SR  
      strtolower($orpaydtl->discountid)=='emp'?$orpaydtl->discamount:number_format(0,2), //DIS_EMP 
      '', //DIS_VIP 
      '', //DIS_GPC 
      number_format($orpaydtl->pwddisc+$orpaydtl->pwddisc2,2,'.',''), //DIS_PWD 
      '', //DIS_G 
      '', //DIS_H 
      '', //DIS_I 
      '', //DIS_J 
      '', //DIS_K 
      '', //DIS_L 
      '', //DIS_VX  
      '' //NTAX_SAL
    ];
  }

  private function setToArray($is_new, $key, $invdtl, $combo=null, $table='invdtl', $cancelled=false) {

    if (!is_null($combo)) {
      $uprice = $combo->product->unitprice;
      $qty = $cancelled
        ? number_format(-1*abs($combo->qty*$invdtl->qty),2)
        : number_format($combo->qty*$invdtl->qty,2);
      $comp2 = $invdtl->product->code;
      $comp3 = number_format($invdtl->qty, 0);

      $this->info(' '.($combo->qty+0).' '.$combo->product->shortdesc);
    } else {
      $uprice = $invdtl->unitprice;
      $qty = $cancelled 
        ? number_format(-1*abs($invdtl->qty),2)
        : number_format($invdtl->qty,2);
      $comp2 = '';
      $comp3 = '';
    }
    
    $grsamt = $cancelled
      ? number_format(-1*abs($uprice*$qty),2)
      : number_format($uprice*$qty,2);
    
    if ($is_new && $key==0) {
      $pax = $invdtl->invhdr->pax.'|1';
      $custno = '';
    } else {
      $pax = '';
      $custno = $invdtl->invhdr->uidcreate;
    }
    
    $catname = ucwords(strtolower($$table->product->prodcat->descriptor), " \t\r\n\f\v-");

    if (array_key_exists($catname, $this->prodtype_breakdown))
      $this->prodtype_breakdown[$catname] += $grsamt;
    else
      $this->prodtype_breakdown[$catname] = $grsamt;
      

    return [
      $invdtl->invhdr->tableno, //TBLNO
      $invdtl->invhdr->uidcreate, //WTRNO
      substr($invdtl->ordrefno,4), //ORDNO
      $pax, //CUSNO pax
      $custno,  //CUSNAME cashier
      $$table->product->code, //PRODNO
      $$table->product->shortdesc,  //PRODNAME
      $qty, //QTY
      $uprice , //UPRICE
      $grsamt, //GRSAMT
      '0.00', //DISC
      $grsamt, //NETAMT
      $invdtl->invhdr->date->format('Ymd'), //ORDDATE
      $invdtl->ordtime.':00',  //ORDTIME
      '', //CATNO
      $catname,  //CATNAME
      $invdtl->lineno, //RECORD 
      substr($invdtl->invhdr->refno,4), //CSLIPNO
      '', //COMP1
      $comp2, //COMP2
      $comp3, //COMP3
      '', //COMP4
      '', //COMP5
      '', //COMPQTY1
      '', //COMPQTY2
      '10.00', //COMPQTY3 time to prep the PROD
      '', //COMPQTY4
      '', //COMPQTY5
      '', //COMPUNIT1
      substr($$table->product->menuprod->menucat->descriptor or '', 0, 6), //COMPUNIT2
      substr($$table->product->menuprod->menucat->descriptor or '', 6, 6), //COMPUNIT3
      $invdtl->product->sectionid, //COMPUNIT4
      '' //COMPUNIT5
    ];
  }

  private function add_record($dbf=null, $data=null) {
    return dbase_add_record($dbf, $data);
  }

  private function close_dbf($dbf) {
    return dbase_close($dbf);
  }

  private function is_groupies(InvdtlModel $invdtl=null) {
    
    if ($invdtl->iscombo && (
      starts_with(strtolower($invdtl->product->code), 's') 
      || starts_with(strtolower($invdtl->product->code), 'f'))) {

      return true;
    }
    return false;
  }

  private function update_products($dbf_path=null) {

    $dbf = is_null($dbf_path)
      ? storage_path().DS.'product'.DS.'PRODUCTS.DBF'
      : $dbf_path;

    if (file_exists($dbf)) {
      
      $db = dbase_open($dbf, 0);
      $recno = dbase_numrecords($db);

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        
        $this->info($row['PRODNO'].' '.$row['PRODNAME']);
        $product = Product::where('code', trim($row['PRODNO']))->first();
        if (is_null($product)) {
          $this->info('no record');
        } else {
          $this->info($product->code);

          $product->shortdesc = trim($row['PRODNAME']);
          if ($product->save()) 
            $this->info('Saved!');
        }


      }

      dbase_close($db);

    }
  }

   

    


  private function salesmtd_fields() {
    return [
      ['TBLNO',     'C', 6], 
      ['WTRNO',     'C', 6], 
      ['ORDNO',     'C', 6], 
      ['CUSNO',     'C', 6], 
      ['CUSNAME',   'C', 25], 
      ['PRODNO',    'C', 6],  
      ['PRODNAME',  'C', 25],  
      ['QTY',       'N', 6,2], 
      ['UPRICE',    'N', 10,2],  
      ['GRSAMT',    'N', 10,2],  
      ['DISC',      'N', 10,2],  
      ['NETAMT',    'N', 10,2],  
      ['ORDDATE',   'D'], 
      ['ORDTIME',   'C', 8], 
      ['CATNO',     'C', 6], 
      ['CATNAME',   'C', 25], 
      ['RECORD',    'N', 3, 0],  
      ['CSLIPNO',   'C', 6], 
      ['COMP1',     'C', 20], 
      ['COMP2',     'C', 20], 
      ['COMP3',     'C', 20], 
      ['COMP4',     'C', 20], 
      ['COMP5',     'C', 20], 
      ['COMPQTY1',  'N', 10,2], 
      ['COMPQTY2',  'N', 10,2],  
      ['COMPQTY3',  'N', 10,2],  
      ['COMPQTY4',  'N', 10,2],  
      ['COMPQTY5',  'N', 10,2],  
      ['COMPUNIT1', 'C', 6], 
      ['COMPUNIT2', 'C', 6], 
      ['COMPUNIT3', 'C', 6], 
      ['COMPUNIT4', 'C', 6],
      ['COMPUNIT5', 'C', 6]
    ];
  }

  private function charges_fields() {
    return [
      ['CSLIPNO',   'C', 6],  
      ['ORDDATE',   'D'],  
      ['ORDTIME',   'C', 8],  
      ['CUSNO',     'C', 6],  
      ['CUSNAME',   'C', 25],  
      ['CHARGPCT',  'N', 6,2],  
      ['GRSCHRG',   'N', 10,2],  
      ['PROMO_PCT', 'N', 6,2],  
      ['PROMO_AMT', 'N', 10,2],  
      ['SR_TCUST',  'N', 3,0],  
      ['SR_BODY',   'N', 3,0],  
      ['SR_DISC',   'N', 10,2],  
      ['VAT',       'N', 10,2],  
      ['SERVCHRG',  'N', 10,2],  
      ['OTHDISC',   'N', 10,2],  
      ['UDISC',     'N', 10,2],  
      ['BANKCHARG', 'N', 10,2],  
      ['TOTCHRG',   'N', 10,2],  
      ['PDAMT',     'N', 10,2],  
      ['PMTDISC',   'N', 10,2],  
      ['BALANCE',   'N', 10,2],  
      ['TERMS',     'C', 10],  
      ['CARDNO',    'C', 20],  
      ['CARDTYP',   'C', 10],  
      ['LASTPD',    'D'],  
      ['REMARKS',   'C', 25],  
      ['CUSADDR1',  'C', 25],  
      ['CUSADDR2',  'C', 25],  
      ['CUSTEL',    'C', 15],  
      ['CUSFAX',    'C', 15],  
      ['CUSCONT',   'C', 25],  
      ['TCASH',     'N', 10,2],  
      ['TCHARGE',   'N', 10,2],  
      ['TSIGNED',   'N', 10,2],  
      ['AGE',       'N', 2,0],  
      ['VAT_XMPT',  'N', 10,2],  
      ['FILLER1',   'N', 10,2],  
      ['FILLER2',   'N', 10,2],  
      ['DIS_PROM',  'N', 10,2],  
      ['DIS_UDISC', 'N', 10,2],  
      ['DIS_SR',    'N', 10,2],  
      ['DIS_EMP',   'N', 10,2],  
      ['DIS_VIP',   'N', 10,2],  
      ['DIS_GPC',   'N', 10,2],  
      ['DIS_PWD',   'N', 10,2],  
      ['DIS_G',     'N', 10,2],  
      ['DIS_H',     'N', 10,2],  
      ['DIS_I',     'N', 10,2],  
      ['DIS_J',     'N', 10,2],  
      ['DIS_K',     'N', 10,2],  
      ['DIS_L',     'N', 10,2],  
      ['DIS_VX',    'N', 10,2],  
      ['NTAX_SAL',  'N', 10,2]
    ];
  }
}
