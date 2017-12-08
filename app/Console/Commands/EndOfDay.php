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
use App\Repositories\Rmis\Invhdr;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;

class EndOfDay extends Command
{
/**
 * The name and signature of the console command.
 *
 * @var string
 */
 protected $signature = 'pos:eod 
                        {date : YYYY-MM-DD}
                        {--brcode=EGC : branch code}
                        {--winpos : print to printer}
                        {--zread : print z reading}
                        {--terminal : print z reading}';

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
protected $invhdr;
protected $orpaydtl;
protected $save_cancelled = true;
protected $payment_type = [1=>'CASH', 2=>'CHRG', 3=>'GCRT', 4=>'SIGN'];
protected $payment_breakdown = [];
protected $gross = 0;
protected $prodtype_breakdown = [];
protected $summary = [];
protected $prodcats = [];
protected $ctrx = 2;
protected $rcpt_lines = [];
protected $print = false;
protected $zread = false;
protected $terminal = false;

protected $zread_lines = [];
protected $zread_gross = 0;

public function __construct(Invdtl $invdtl, Orpaydtl $orpaydtl, Invhdr $invhdr) {
  parent::__construct();
  $this->invdtl = $invdtl;
  $this->orpaydtl = $orpaydtl;
  $this->invhdr = $invhdr;
  $this->assert = new AssertBag;
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

    $this->print = $this->option('winpos')
      ? true : false;

    $this->zread = $this->option('zread')
      ? true : false;

    $this->terminal = $this->option('terminal')
      ? true : false;
    
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
      $this->info("\tCHECKING DATA INTEGRITY");
      $this->data_check($date);

      

      //return;

      $this->info('');
      $this->info("\tPROCESSING SALESMTD");
      
      $this->salesmtd($date);

      $t = 0;
      $this->info('');
      foreach ($this->prodcats as $key => $value) {
        $this->info(str_pad($key,4).': '.str_pad(number_format($value, 2), 10, ' ', STR_PAD_LEFT));
        $t += $value;
      }
      $this->info('-----------------');
      $this->info(str_pad(number_format($t,2),15,' ',STR_PAD_LEFT));

      $this->info('');
      $this->info('*******************************************');
      $this->info("\tPROCESSING PAYMENTS");
      $this->info('');
      $this->charges($date);
      

      $this->info('');
      $this->info('*** Discounts ***');
      $this->info('SC '.str_pad(number_format($this->summary['disc']['sc'],2),12,' ',STR_PAD_LEFT));
      $this->info('PWD '.str_pad(number_format($this->summary['disc']['pwd'],2),11,' ',STR_PAD_LEFT));
      $this->info('OTHR '.str_pad(number_format($this->summary['disc']['other'],2),10,' ',STR_PAD_LEFT));
      $this->info('-----------------');
      $this->info(str_pad(number_format($this->summary['tot_disc'],2),15,' ',STR_PAD_LEFT));

      $this->info('');
      $this->info('Tax Exempt: '.str_pad(number_format($this->summary['tot_vatxmpt'],2),8,' ',STR_PAD_LEFT));
      $this->info('Srv Charge: '.str_pad(number_format($this->summary['tot_svchrg'],2),8,' ',STR_PAD_LEFT));
      

      
      $datas = $this->getZreading($date);
      $this->zreadfile($date, $datas);
      //return dd($this->print);
      if ($this->print)
        $this->zreadprint($datas);

      $used_terminal = $this->invhdr->usedTerminal($date);
      foreach ($used_terminal as $key => $terminalid) {
        $page = ($key+1).' of '.count($used_terminal);
        $datas = $this->getTzreading($date, $terminalid, $page);
        $this->zreadfile($date, $datas, $terminalid);
        if ($this->terminal)
          $this->zreadprint($datas);
      }

      $this->info('');
      $this->info('----------------------------------------');
      $this->zreadScreen($this->zreadLines($date));

      if ($this->zread)
        $this->printToPrinter($this->zread_lines);

      if ($this->assert->assert)
        $this->info('');
      else {
        $this->info('');
        $this->info('Notes:');
        foreach ($this->assert->getErrors() as $key => $value) {
          $this->info(' - '.$value);
        }
      }


      /*
      foreach ($this->summary['c'] as $key => $value) {
        $this->info(strtoupper($key).' '.number_format($value, 2));
      }

      $connector = new FilePrintConnector("lpt1");
      $printer = new Printer($connector);
      $printer->text(str_pad('Z-READING', 40, " ", STR_PAD_BOTH));
      $printer->text(bpad('Sat, Nov 18, 2017', 40));
      $printer->text("*** Discounts ***\n");
      $printer->text();
      $printer->text("SC: ".str_pad(number_format($this->summary['disc']['sc'],2),11,' ',STR_PAD_LEFT)."\n");


      $printer->cut();
      $printer->close();

      $connector = new FilePrintConnector("lpt1");
      $printer = new Printer($connector);
      $printer->text("       ALQUIROS FOOD CORPORATION\n");
      $printer->text("         (GILIGAN'S RESTAURANT)\n");
      $printer->text("             SM by the Bay\n");
      $printer->text("      BLDG H, UNITS 11-16 BRGY.076\n");
      $printer->text("      SM BUSINESS PARK, PASAY CITY\n");
      $printer->text("          #205-257-440-005 VAT\n");
      $printer->text("            S/N AZLF9270080W\n");
      $printer->text("             MIN# 090119166\n");
      $printer->cut();
      $printer->close();

      $this->info('done');
      */
      
      return 1;

    }
  }

  private function zreadprint($lines) {

    if (is_null($lines)) 
      return false;

    return $this->printToPrinter($lines);
  }

  private function printToPrinter(array $array) {
    //$connector = new FilePrintConnector("lpt1");
    if (is_null($array)) 
      return false;

    $printer = new Printer(new FilePrintConnector("lpt1"));

    foreach ($array as $key => $content) {
      $printer->text($content.PHP_EOL); 
    }

    $printer->cut();
    $printer->close();

    return true;
  }

  private function zreadfile(Carbon $date, $lines, $terminalid=NULL) {
    
    $logfile = is_null($terminalid)
      ? "C:\ZREAD".DS.$date->format('Y').DS.$date->format('m').DS.'ZREPORT-'.$date->format('Ymd').'.txt'
      : "C:\ZREAD".DS.$date->format('Y').DS.$date->format('m').DS.'ZREPORT-'.$date->format('Ymd').'-'.$terminalid.'.txt';

    $dir = pathinfo($logfile, PATHINFO_DIRNAME);

    if(!is_dir($dir))
      mkdir($dir, 0775, true);

    $new = file_exists($logfile) ? false : true;
    if($new){
      $handle = fopen($logfile, 'w+');
      chmod($logfile, 0775);
    } else
      $handle = fopen($logfile, 'w+');

    if (!is_null($lines)) {
      foreach ($lines as $key => $content) {
        fwrite($handle, $content.PHP_EOL);
      }
    }
    
    fclose($handle);
    
  }

  private function getZreading(Carbon $date) {
    
    $invhdr = $this->invhdr
                ->skipCache()
                ->zreadsales($date)
                ->all();

    $orpaydtls = $this->invhdr
                ->skipCache()
                ->zreadpay($date)
                ->all();

    $invhdr = is_null($invhdr) ? NULL : $invhdr->first();

    if (is_null($invhdr))
      return NULL;
    
    return $this->getLines($date, $invhdr, $orpaydtls);
      
  }

  private function getTzreading(Carbon $date, $terminalid, $page=NULL) {
    
    $invhdr = $this->invhdr
                ->skipCache()
                ->tzreadsales($date, $terminalid)
                ->all();

    $orpaydtls = $this->invhdr
                ->skipCache()
                ->tzreadpay($date, $terminalid)
                ->all();

    $invhdr = is_null($invhdr) ? NULL : $invhdr->first();

    if (is_null($invhdr))
      return NULL;
    
    return $this->getLines($date, $invhdr, $orpaydtls, $terminalid, $page);
      
  }

  private function getLines($date, $invhdr, $orpaydtls, $terminalid=NULL, $page=NULL) {

    $csh = 0;
    $chgr = 0;
    $gc = 0;
    $sign = 0;

    if (!is_null($orpaydtls)) {
      foreach ($orpaydtls as $key => $pay) {
        switch ($pay->paytype) {
          case '1':
            $csh = $pay->amount;
            break;
          case '2':
            $chgr = $pay->amount;
            break;
          case '3':
            $gc = $pay->amount;
            break;
          case '4':
            $sign = $pay->amount;
            break;
        }
      }
    }

    $title = is_null($terminalid)
      ? 'Z-READING'
      : 'Z-READING ('.$terminalid.')';

    $lines = [];

    array_push($lines, bpad($title, 40));
    array_push($lines, bpad($date->format('M-d-Y'), 40));
    array_push($lines, bpad(' ', 40));
    array_push($lines, bpad('=', 40, '='));
    array_push($lines, bpad(' ', 40));
    
    array_push($lines, rpad('GROSS SALES', 40));
    array_push($lines, rpad('  VAT Sales', 28).lpad(number_format($invhdr->vtotal, 2), 12));
    array_push($lines, rpad('  VAT Exempt Sales', 28).lpad(number_format($invhdr->xtotal, 2), 12));
    array_push($lines, rpad('  Zero Rated Sales', 28).lpad(number_format($invhdr->ztotal, 2), 12));
    array_push($lines, rpad('  (-) Discount', 28).lpad(number_format($invhdr->discamount, 2), 12));
    array_push($lines, '  --------------------------------------');
    array_push($lines, rpad('  TOTAL GROSS SALES', 28).lpad(number_format(($invhdr->vtotal+$invhdr->xtotal+$invhdr->ztotal)-$invhdr->discamount, 2), 12));
    array_push($lines, bpad(' ', 40));

    $net = 0;
    array_push($lines, rpad('NET SALES', 40));
    array_push($lines, $this->t('  VAT Sales', $invhdr->vatsales));           $net += $invhdr->vatsales;
    array_push($lines, $this->t('  VAT Exempt Sales', $invhdr->vatxsales));   $net += $invhdr->vatxsales;
    array_push($lines, $this->t('  Zero Rated Sales', $invhdr->vatzsales));   $net += $invhdr->vatzsales;
    array_push($lines, $this->t('  VAT Amount', $invhdr->vatamount));         $net += $invhdr->vatamount;
    array_push($lines, $this->t('(+) Service Charge', $invhdr->svcamount));   $net += $invhdr->svcamount;
    array_push($lines, $this->t('(+) Tax', $invhdr->taxamount));              $net += $invhdr->taxamount;
    array_push($lines, $this->t('(-) Senior Discount', $invhdr->scdisc));     $net -= $invhdr->scdisc;
    array_push($lines, $this->t('(-) PWD Discount', $invhdr->pwddisc));       $net -= $invhdr->pwddisc;
    array_push($lines, '  --------------------------------------');
    array_push($lines, $this->t('  TOTAL NET SALES', $net));
    array_push($lines, bpad(' ', 40));

    $tender = 0;
    array_push($lines, rpad('TENDERS', 40));
    array_push($lines, $this->t('  Cash', $csh));             $tender += $csh;
    array_push($lines, $this->t('  Credit Card', $chgr));     $tender += $chgr;
    array_push($lines, $this->t('  Gift Certificate', $gc));  $tender += $gc;
    array_push($lines, $this->t('  Signed Chit', $sign));     $tender += $sign;
    array_push($lines, '  --------------------------------------');
    array_push($lines, $this->t('  TOTAL TENDERS', $tender));
    array_push($lines, bpad(' ', 40));
    
    array_push($lines, rpad('CASH FROM SALES', 40));
    array_push($lines, $this->t('  Cash', $csh));
    array_push($lines, $this->t('(-) Change', $invhdr->totchange));
    array_push($lines, '  --------------------------------------');
    array_push($lines, $this->t('  TOTAL CASH FROM SALES', ($csh-$invhdr->totchange)));
    array_push($lines, bpad(' ', 40));

    array_push($lines, '----------------------------------------');
    array_push($lines, $this->t('Posted Txn Count', $invhdr->txncount));
    array_push($lines, $this->t('Cancelled Txn Count', $invhdr->txncancel));
    array_push($lines, $this->t('Total Pax', $invhdr->pax));
    array_push($lines, $this->t('SC Pax', $invhdr->scpax));
    array_push($lines, $this->t('PWD Pax', $invhdr->pwdpax));
    array_push($lines, '----------------------------------------');


    array_push($lines, bpad('* END OF Z-REPORT *', 40));
    array_push($lines, bpad(c()->format('m/d/Y H:i:s'), 40));
    if (!is_null($page))
      array_push($lines, bpad($page, 40));

    return $lines;
  }

  private function zreadLines(Carbon $date) {

    $a = 0;
    

    array_push($this->zread_lines, bpad('IMPORT Z-READING', 40));
    array_push($this->zread_lines, bpad($date->format('D M-d-Y'), 40));
    array_push($this->zread_lines, '----------------------------------------');
    array_push($this->zread_lines, rpad('Gross Sales', 28).lpad(number_format($this->summary['a']['gross'], 2), 12));             $a += $this->summary['a']['gross'];
    array_push($this->zread_lines, rpad(' Less Discount', 28).lpad('-'.number_format($this->summary['tot_disc'], 2), 12));            $a -= $this->summary['tot_disc'];
    array_push($this->zread_lines, rpad(' Less Tax-Exemption', 28).lpad('-'.number_format($this->summary['tot_vatxmpt'], 2), 12));    $a -= $this->summary['tot_vatxmpt'];
    array_push($this->zread_lines, lpad('-----------------', 40));
    array_push($this->zread_lines, rpad('     TOTAL (A)', 28).lpad(number_format($a, 2), 12));
    //array_push($this->zread_lines, lpad('-----------------', 40));
    //array_push($this->zread_lines, rpad(' Less 12% VAT', 28).lpad('-'.number_format($this->summary['tot_vat'], 2), 12));    $a -= $this->summary['tot_vat'];
    //array_push($this->zread_lines, lpad('-----------------', 40));
    //array_push($this->zread_lines, rpad('NET SALES', 28).lpad(number_format($a, 2), 12));
    array_push($this->zread_lines, '----------------------------------------');
    array_push($this->zread_lines, bpad(' ', 40));
    
    $b = 0;
    array_push($this->zread_lines, rpad('Revenue:', 40));
    foreach ($this->prodtype_breakdown as $key => $value) {
      array_push($this->zread_lines, rpad('  '.strtoupper(substr($key,0,15)), 28).lpad(number_format($value, 2), 12));           
      $b += $value;
    }
    array_push($this->zread_lines, rpad('  GROSS SIGNED', 28).lpad('-'.number_format($this->summary['c']['signed'], 2), 12));   $b -= $this->summary['c']['signed'];
    array_push($this->zread_lines, rpad('  TAX EXEMPT', 28).lpad('-'.number_format($this->summary['tot_vatxmpt'], 2), 12));     $b -= $this->summary['tot_vatxmpt'];
    array_push($this->zread_lines, rpad('  DISCOUNT/S', 28).lpad('-'.number_format($this->summary['tot_disc'], 2), 12));        $b -= $this->summary['tot_disc'];
    array_push($this->zread_lines, lpad('-----------------', 40));
    array_push($this->zread_lines, rpad('     TOTAL (B)', 28).lpad(number_format($b, 2), 12));
    array_push($this->zread_lines, '----------------------------------------');
    array_push($this->zread_lines, bpad(' ', 40));

    $c = 0;
    array_push($this->zread_lines, rpad('Collections:', 40));
    array_push($this->zread_lines, rpad('  CASH', 28).lpad(number_format($this->summary['c']['cash'], 2), 12));   $c += $this->summary['c']['cash'];
    array_push($this->zread_lines, lpad('-----------------', 40));
    array_push($this->zread_lines, rpad('     TOTAL (C)', 28).lpad(number_format($c, 2), 12));
    array_push($this->zread_lines, lpad('-----------------', 40));
    array_push($this->zread_lines, rpad('  Service Charge', 28).lpad(number_format($this->summary['tot_svchrg'], 2), 12));   $c += $this->summary['tot_svchrg'];
    array_push($this->zread_lines, lpad('-----------------', 40));
    array_push($this->zread_lines, rpad('TOTAL CASH', 28).lpad(number_format($c, 2), 12));
    array_push($this->zread_lines, '----------------------------------------');
    array_push($this->zread_lines, bpad(' ', 40));

    $d = 0;
    array_push($this->zread_lines, rpad('Discounts:', 40));
    array_push($this->zread_lines, rpad('  SR.CITIZEN', 28).lpad(number_format($this->summary['disc']['sc'], 2), 12));   $d +=  $this->summary['disc']['sc'];
    array_push($this->zread_lines, rpad('  PWD', 28).lpad(number_format($this->summary['disc']['pwd'], 2), 12));   $d +=  $this->summary['disc']['pwd'];
    array_push($this->zread_lines, rpad('  OTHER DISC', 28).lpad(number_format($this->summary['disc']['other'], 2), 12));   $d +=  $this->summary['disc']['other'];
    array_push($this->zread_lines, lpad('-----------------', 40));
    array_push($this->zread_lines, rpad('     TOTAL DISC.', 28).lpad(number_format($d, 2), 12));
    array_push($this->zread_lines, lpad('-----------------', 40));
    array_push($this->zread_lines, rpad('  TAX EXEMPT', 28).lpad(number_format($this->summary['tot_vatxmpt'], 2), 12));
    array_push($this->zread_lines, '----------------------------------------');
    array_push($this->zread_lines, bpad(' ', 40));
    array_push($this->zread_lines, bpad('Printed on: '.c()->format('m/d/Y h:i:s A'), 40));



    return $this->zread_lines;
  }

  private function zreadScreen(array $lines) {
    foreach ($lines as $key => $content) {
      $this->info($content); 
    }
  }



  private function t($a, $b) {
    return rpad($a, 28).lpad(number_format($b, 2), 12);
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
      $tot_svc = 0;
      $tot_nosvc = 0;
      $ptype = null;
      $terminals = [];
      
      $this->info('+------------------------------------------------------------------------+');
      $this->info(
            str_pad('#',4,' ',STR_PAD_LEFT).' '.
            str_pad('TYPE',4,' ',STR_PAD_LEFT).' '.
            str_pad('REFNO',4,' ',STR_PAD_LEFT).' '.
            str_pad('TENDERED',11,' ',STR_PAD_LEFT).' '.
            str_pad('AMT PAID',10,' ',STR_PAD_LEFT).' '.
            str_pad('CHANGE',10,' ',STR_PAD_LEFT).' '.
            str_pad('SVCHRG',8,' ',STR_PAD_LEFT).' '.
            str_pad('NOCHRG',10,' ',STR_PAD_LEFT).' '.
            str_pad('MID',3,' ',STR_PAD_LEFT)
          );
      $this->info('+------------------------------------------------------------------------+');
      
      foreach ($orpaydtls as $key => $orpaydtl) {
        $true_charge = 0;
        if (in_array($orpaydtl->paytype, [1,2])) {
          $this->add_record($dbf_c,  $this->setOrpaydtl($orpaydtl));
          if ($orpaydtl->paytype=='1') {
            $ptype = 'CASH';
            $true_charge = $orpaydtl->amounts-$orpaydtl->totchange;
            $tot += $true_charge;
            $tot_svc += $orpaydtl->svcamount;
            $tot_nosvc += $true_charge-$orpaydtl->svcamount;
          } else {
            $ptype = 'CHGR'; 
            $true_charge = $orpaydtl->amounts;
            $tot += $orpaydtl->amounts;
            $tot_svc += $orpaydtl->svcamount;
            $tot_nosvc += $true_charge-$orpaydtl->svcamount;
          }
        }

        //if ($orpaydtl->paytype=='10') {
        if ($orpaydtl->paytype=='4') {
          $this->add_record($dbf_s,  $this->setOrpaydtl($orpaydtl));
          $ptype = 'SIGN';
          $true_charge = $orpaydtl->amounts;
          //$tot += $orpaydtl->amounts; 
          //$tot_svc += $orpaydtl->svcamount;
          //$tot_nosvc += $true_charge-$orpaydtl->svcamount;
        }

        if (in_array($orpaydtl->paytype, [1,2,4])) {

          if (in_array($orpaydtl->paytype, [1,2])) {
            $this->info(
              str_pad(($key+1),4,' ',STR_PAD_LEFT).' '.
              $ptype.' '.
              substr($orpaydtl->invrefno,4).' '.
              str_pad(number_format($orpaydtl->amounts,2),10,' ',STR_PAD_LEFT).' '.
              str_pad(number_format($true_charge,2),10,' ',STR_PAD_LEFT).' '.
              str_pad(number_format($orpaydtl->amounts-$true_charge,2),10,' ',STR_PAD_LEFT).' '.
              str_pad(number_format($orpaydtl->svcamount,2),8,' ',STR_PAD_LEFT).' '.
              str_pad(number_format($true_charge-$orpaydtl->svcamount,2),10,' ',STR_PAD_LEFT).' '.
              $orpaydtl->terminalid
            );
          }

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

      $this->info('+------------------------------------------------------------------------+');
      $this->info('TOTAL CASH: '.str_pad(number_format($tot,2),26,' ',STR_PAD_LEFT)
                          .str_pad(number_format($tot_svc,2),20,' ',STR_PAD_LEFT)
                          .str_pad(number_format($tot_nosvc,2),11,' ',STR_PAD_LEFT));
      $this->info('+------------------------------------------------------------------------+');
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
          $this->line('================================================');
          $this->line("\t".substr($invdtl->invhdr->refno,4));
          $this->line('================================================');
          $last_clspno=$invdtl->invhdr->refno;
          $flag = true;
        }

        if ($invdtl->cancelled) {
          $this->line('====================CANCELLED============================');
          $this->info((-1*abs($invdtl->qty)+0).' '.str_pad($invdtl->product->shortdesc,25,' ')." ".str_pad(number_format($invdtl->unitprice,2),8,' ',STR_PAD_LEFT)."\t".str_pad(number_format(-1*abs($invdtl->amount),2),8,' ',STR_PAD_LEFT));
        } else {
          $this->info(($invdtl->qty+0).' '.str_pad($invdtl->product->shortdesc,25,' ')." ".str_pad(number_format($invdtl->unitprice,2),8,' ',STR_PAD_LEFT)."\t".str_pad(number_format($invdtl->amount,2),8,' ',STR_PAD_LEFT));
        }
                  
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

      $this->line('================================================');
      $this->info('+----------------------------------------------+');
      $this->comment('GROSS: '.number_format($tot, 2));
      $this->info('+----------------------------------------------+');
      $this->info(' ');

      $this->zread_gross = $tot;

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
      number_format($orpaydtl->scpax+$orpaydtl->pwdpax,0,'.',''), //SR_BODY 
      number_format($orpaydtl->scdisc+$orpaydtl->pwddisc,2,'.',''), //SR_DISC 
      $orpaydtl->vatamount, //VAT 
      number_format(0,2), //$orpaydtl->svcamount, //SERVCHRG  
      //$orpaydtl->discamount, //OTHDISC 
      number_format($orpaydtl->discamount,2,'.',''), //OTHDISC 
      number_format(0,2), //UDISC 
      $bnkchrg, //BANKCHARG 
      number_format($true_charge-$orpaydtl->svcamount,2,'.',''), //$orpaydtl->amount, //TOTCHRG 
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
      number_format($orpaydtl->scdisc,2,'.',''), //DIS_SR  
      strtolower($orpaydtl->discountid)=='emp'?$orpaydtl->discamount:number_format(0,2), //DIS_EMP 
      '', //DIS_VIP 
      '', //DIS_GPC 
      number_format($orpaydtl->pwddisc,2,'.',''), //DIS_PWD 
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
       $sec = lpad($invdtl->lineno.$combo->seqno, 2, '0');
    } else {
      $uprice = $invdtl->unitprice;
      $qty = $cancelled 
        ? number_format(-1*abs($invdtl->qty),2)
        : number_format($invdtl->qty,2);
      $comp2 = '';
      $comp3 = '';
      $sec = lpad($invdtl->lineno, 2, '0');
    }
    
    $grsamt = $cancelled
      ? number_format(-1*abs($uprice*$qty),2,'.','')
      : number_format($uprice*$qty,2,'.','');
    
    if ($is_new && $key==0) {
      $pax = $invdtl->invhdr->pax.'|1';
      $custno = '';
    } else {
      $pax = '';
      $custno = $invdtl->invhdr->uidcreate;
    }
    
    $catname = ucwords(strtolower($$table->product->prodcat->descriptor), " \t\r\n\f\v-");

    if (array_key_exists($catname, $this->prodtype_breakdown)) {
      $this->prodtype_breakdown[$catname] += $grsamt;
      //$this->info('*************************************************************+');
    }
    else {
      $this->prodtype_breakdown[$catname] = $grsamt;
      //$this->info('*************************************************************=');
    }


      

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
      $invdtl->ordtime.':'.$sec,  //ORDTIME
      '', //CATNO
      $catname,  //CATNAME
      //$invdtl->lineno, //RECORD 
      $sec, //RECORD 
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

  private function  data_check(Carbon $date) {

    $invhdrs = $this->invhdr
                ->skipCache()
                ->orderBy('refno')
                ->with([
                  'invdtls.product'=>function($q) {
                    $q->select(['code','descriptor','shortdesc','iscombo','id', 'prodcatid']);
                  },
                  'scinfos',
                  'pwdinfos',
                  'orpaydtls',
                  'orderhdrs.orderdtls'
                ])
                ->findWhere(['date'=>$date->format('Y-m-d'), 'posted'=>1, 'cancelled'=>0]);

    //$this->info(count($invhdrs));
    $this->initSummary();
    foreach ($invhdrs as $key => $invhdr) {

      $adtl = $this->assertInvdtl($invhdr);
      $asc = $this->assertScinfo($invhdr);
      $apwd = $this->assertPwdinfo($invhdr);
      $aor = $this->assertOrpaydtl($invhdr);

      $this->setSummary($invhdr);

      //$tdtl = $adtl ? 'yes':' no';
      $tdtl   = $adtl->assert ? '-':'X';
      $tsc    = $asc->assert  ? '-':'X';
      $tpwd   = $apwd->assert ? '-':'X';
      $tor    = $aor->assert  ? '-':'X';

      if (!$adtl->assert || !$asc->assert || !$apwd->assert || !$aor->assert) { 
        $text = str_pad($key,3,' ',STR_PAD_LEFT).' '.$invhdr->srefno().' '.$tdtl.' '.$tsc.' '.$tpwd.' '.$tor;
        $this->info($text);

        if (!$adtl->assert)
          $this->assert->addError($adtl->getErrors());
        if (!$asc->assert)
          $this->assert->addError($asc->getErrors());
        if (!$apwd->assert)
          $this->assert->addError($apwd->getErrors());
        if (!$aor->assert)
          $this->assert->addError($aor->getErrors());

      }
      
    }


    $this->summary['a']['gross'] = $this->summary['a']['gross']-$this->summary['c']['signed'];
    $this->summary['tot_vat'] = $this->summary['tot_vat']-$this->summary['tot_vat_signed'];




  }


  private function assertInvdtl($invhdr) {
    $assert = new AssertBag;

    //$this->info($invhdr->totinvline.'-'.count($invhdr->invdtls));

    if (count($invhdr->invdtls)<=0 || is_null($invhdr->invdtls))
      $assert->addError($invhdr->srefno().': No invdtl');

    if ($invhdr->totinvline > count($invhdr->invdtls))
      $assert->addError($invhdr->srefno().': Totinvline is greater than actual invdtl');

    if ($invhdr->totinvline < count($invhdr->invdtls)) {
      $ctr = 0;
      foreach ($invhdr->invdtls as $key => $invdtl) {
        if ($invdtl->cancelled==0)
          $ctr++;
      }

      if ($invhdr->totinvline < $ctr)
        $assert->addError($invhdr->srefno().': Actual invdtl do not match totinvline');
    }

    $ctr_gross = 0;
    $prodcats = [];
    foreach ($invhdr->invdtls as $key => $invdtl) {
      if ($invdtl->cancelled==0) {

        if ($this->is_groupies($invdtl)) {
           
          $invdtl->product->load(['combos'=>function($q){
              $q->with('product.prodcat')
                ->orderBy('seqno');
            }]);

          foreach ($invdtl->product->combos as $key => $combo) {
            $ctr_gross += $combo->qty*($invdtl->qty*$combo->product->unitprice);

            $this->info(
              str_pad($this->ctrx, 5).' '
              .$invhdr->srefno().' '
              .str_pad($invdtl->product->code, 3).' '
              .str_pad($combo->product->shortdesc, 25).' '
              .str_pad(number_format($combo->qty,2)+0, 4, ' ', STR_PAD_LEFT).' '
              .str_pad(number_format($invdtl->qty,2)+0, 4, ' ', STR_PAD_LEFT).' '
              .str_pad(number_format($combo->product->unitprice,2), 6, ' ', STR_PAD_LEFT).' '
              .str_pad(number_format($combo->qty*($invdtl->qty*$combo->product->unitprice),2), 6, ' ', STR_PAD_LEFT).' '
              .str_pad($combo->product->prodcat->code, 25)
            );
            $this->ctrx++;

            if (array_key_exists($combo->product->prodcat->code, $this->prodcats))
              $this->prodcats[$combo->product->prodcat->code] += $combo->qty*($invdtl->qty*$combo->product->unitprice);
            else
              $this->prodcats[$combo->product->prodcat->code] = $combo->qty*($invdtl->qty*$combo->product->unitprice);
          }

        } else {
          $ctr_gross += $invdtl->amount;
          
          $invdtl->product->load('prodcat');
          
          $this->info(
            str_pad($this->ctrx, 5).' '
            .$invhdr->srefno().' '
            .str_pad('', 3).' '
            .str_pad($invdtl->product->shortdesc, 25).' '
            .str_pad(number_format($invdtl->qty,2)+0, 4, ' ', STR_PAD_LEFT).' '
            .str_pad('', 4, ' ', STR_PAD_LEFT).' '
            .str_pad(number_format($invdtl->unitprice,2), 6, ' ', STR_PAD_LEFT).' '
            .str_pad(number_format($invdtl->amount,2), 6, ' ', STR_PAD_LEFT).' '
            .str_pad($invdtl->product->prodcat->code, 25)
          );
          $this->ctrx++;

          if (array_key_exists($invdtl->product->prodcat->code, $this->prodcats))
              $this->prodcats[$invdtl->product->prodcat->code] += $invdtl->amount;
            else
              $this->prodcats[$invdtl->product->prodcat->code] = $invdtl->amount;
        }
      }
    }
    if (number_format($ctr_gross,2)!==number_format($invhdr->vtotal,2))
      $assert->addError($invhdr->srefno().': Gross amount do not match vtotal');
    //if ($invhdr->refno=='0000027226')
    //  $this->info('Gross: '.$invhdr->srefno().' '.$ctr_gross.' '.$invhdr->vtotal);

    $this->summary['a']['gross'] += $invhdr->vtotal;

    return $assert;
  }

  private function assertOrpaydtl($invhdr) {
    $assert = new AssertBag;

    if (count($invhdr->orpaydtls)<=0 || is_null($invhdr->orpaydtls))
      $assert->addError($invhdr->srefno().': No orpaydtls');

    if ($invhdr->totpayline > count($invhdr->orpaydtls))
      $assert->addError($invhdr->srefno().': Totpayline is greater than actual orpaydtl');

    if ($invhdr->totpayline < count($invhdr->orpaydtls)) {
      $ctr = 0;
      foreach ($invhdr->orpaydtls as $key => $orpaydtl) {
        if ($orpaydtl->cancelled==0)
          $ctr++;
      }

      if ($invhdr->totpayline < $ctr)
        $assert->addError($invhdr->srefno().': Actual orpaydtl do not match totpayline');
    }

    $ctr_totpaid = 0;
    foreach ($invhdr->orpaydtls as $key => $orpaydtl) {
      if ($orpaydtl->cancelled==0) {
        $ctr_totpaid += $orpaydtl->amount;

        

        switch ($orpaydtl->paytype) {
          case '1':
            $this->summary['c']['cash'] += $orpaydtl->amount;
            //$this->summary['tot_vat'] += $invhdr->vatamount;
            break;
          case '3':
            if (array_key_exists('charge', $this->summar['c']))
              $this->summar['c']['charge'] += $orpaydtl->amount;
            else
              $this->summar['c']['charge'] = $orpaydtl->amount;
            break;
          case '4':
            $this->summary['c']['signed'] += $orpaydtl->amount;
            $this->summary['tot_vat_signed'] += $invhdr->vatamount;
            break;
          default:
            if (array_key_exists('other', $this->summar['c']))
              $this->summar['c']['other'] += $orpaydtl->amount;
            else
              $this->summar['c']['other'] = $orpaydtl->amount;
            break;
        }

      }
    }

    $this->summary['c']['cash'] = ($this->summary['c']['cash'] - $invhdr->totchange) - $invhdr->svcamount;

    if (number_format($ctr_totpaid,2)!==number_format($invhdr->totpaid,2))
      $assert->addError($invhdr->srefno().': Total orpaydtl amount do not match totpaid');

    if (number_format($ctr_totpaid-$invhdr->totchange,2)!==number_format($invhdr->totsales,2))
      $assert->addError($invhdr->srefno().': Total orpaydtl amount less totchange do not match totsales');

    return $assert;
  }

  private function assertScinfo($invhdr) {
    $assert = new AssertBag;

    if ($invhdr->scpax > count($invhdr->scinfos))
      $assert->addError($invhdr->srefno().': Scpax is greater than actual scinfo');

    if ($invhdr->scpax < count($invhdr->scinfos)) {
      $ctr = 0;
      foreach ($invhdr->scinfos as $key => $scinfo) {
        if ($scinfo->cancelled==0)
          $ctr++;
      }

      if ($invhdr->scpax < $ctr)
        $assert->addError($invhdr->srefno().': Actual scinfo do not match scpax');
    }

    if ($invhdr->scdisc>0 && $invhdr->scpax<=0)
        $assert->addError($invhdr->srefno().': With SC discount but have no scpax');


    return $assert;
  }

  private function assertPwdinfo($invhdr) {
    $assert = new AssertBag;

    if ($invhdr->pwdpax > count($invhdr->pwdinfos))
      $assert->addError($invhdr->srefno().': Pwdpax is greater than actual pwdinfo');

    if ($invhdr->pwdpax < count($invhdr->pwdinfos)) {
      $ctr = 0;
      foreach ($invhdr->pwdinfos as $key => $pwdinfo) {
        if ($pwdinfo->cancelled==0)
          $ctr++;
      }

      if ($invhdr->pwdpax < $ctr)
        $assert->addError($invhdr->srefno().': Actual pwdinfo do not match pwdpax');
    }

    if ($invhdr->pwddisc>0 && $invhdr->pwdpax<=0)
        $assert->addError($invhdr->srefno().': With PWD discount but have no pwdpax');


    return $assert;
  }

  private function initSummary() {
    $this->summary['disc']['sc'] = 0;
    $this->summary['disc']['pwd'] = 0;
    $this->summary['disc']['other'] = 0;

    $this->summary['tot_disc'] = 0;
    $this->summary['tot_vatxmpt'] = 0;
    $this->summary['tot_svchrg'] = 0;
    $this->summary['tot_vat'] = 0;
    $this->summary['tot_vat_signed'] = 0;
    
    $this->summary['a']['gross'] = 0;

    $this->summary['c']['cash'] = 0;
    $this->summary['c']['signed'] = 0;
  }

  private function setSummary($invhdr) {

    $this->summary['disc']['sc']    += $invhdr->scdisc;
    $this->summary['disc']['pwd']   += $invhdr->pwddisc;
    $this->summary['disc']['other'] += $invhdr->discamount;

    $this->summary['tot_disc']      += ($invhdr->scdisc + $invhdr->pwddisc + $invhdr->discamount);
    $this->summary['tot_vatxmpt']   += $invhdr->vatxmpt;
    $this->summary['tot_svchrg']    += $invhdr->svcamount;
    $this->summary['tot_vat']       += $invhdr->vatamount; // moved to assert orpaydtl


  }
}

class AssertBag {
  public $assert = true;
  protected $errorBag = [];

  public function getErrors() {
    return $this->errorBag;
  }

  public function getAssert() {
    return $this->assert;
  }

  public function addError($error) {

    if (is_array($error))
      foreach ($error as $key => $value)
        $this->push_error($value);
    else
      $this->push_error($error);
      
    return false;
  }

  private function push_error($e) {
    array_push($this->errorBag, $e);
    if ($this->assert)
      $this->assert = false;
  }

  public function __toString() {
    return $this->getAssert();
  }
}
