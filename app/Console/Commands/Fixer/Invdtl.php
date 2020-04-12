<?php namespace App\Console\Commands\Fixer;

use App\Models\Rmis\Pwdinfo;
use App\Models\Rmis\Scinfo;
use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Rmis\Product;
use App\Models\Rmis\Invdtl as InvdtlModel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use App\Http\Controllers\SalesmtdController as SalesmtdCtrl;
use App\Repositories\Rmis\Invdtl as InvdtlRepo;
use App\Repositories\Rmis\Orpaydtl;
use App\Repositories\Rmis\Invhdr;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;

class Invdtl extends Command
{
/**
 * The name and signature of the console command.
 *
 * @var string
 */
  protected $signature = 'pos:fix invdtl 
                        {mode : invdt,sc}
                        {refno : 0000000000}
                        {--force=false}';

  protected $invdtl;
  protected $invhdr;
  protected $orpaydtl;
 // protected $assert;


  public function __construct(InvdtlRepo $invdtl, Orpaydtl $orpaydtl, Invhdr $invhdr) {
    parent::__construct();
    $this->invdtl = $invdtl;
    $this->orpaydtl = $orpaydtl;
    $this->invhdr = $invhdr;
    //this->assert = new AssertBag;
  }


  public function handle() {

    $refno = lpad($this->argument('refno'), 10, '0');
    $invhdr = $this->invhdr->findWhere(['refno'=>$refno])->first();

    if (is_null($invhdr)) {
      $this->error('Receipt #: '.$refno.' not found!');
      exit;
    }

    switch ($this->argument('mode')) {
      case 'invdtl':
        $this->fixInvdtl($invhdr);
        break;
       case 'sc':
        $this->fixSenior($invhdr);
        break;
      case 'pwd':
        $this->fixPwd($invhdr);
        break;
      default:
        $this->error('Unknown mode!');
        break;
    }

    exit;
  }

  private function fixPwd($invhdr) {
    $this->info(' ');
    $this->info('PWDINFO:');
    
    $this->info($invhdr->pwdpax);

    $lineno = count($invhdr->pwdinfos);
    

    if ($invhdr->pwdpax > $lineno) {
      //
      $this->info('CASE 1');
    } elseif ($invhdr->pwdpax < $lineno) {
      
      $this->info('CASE 2');
      
      if ($invhdr->pwddisc<=0 && $invhdr->pwddisc2<=0) {
        
        foreach ($invhdr->pwdinfos as $key => $sc) {
          $this->info(($key+1).' '.$sc->fullname);      
          if (empty($sc->refno)) {          
            $this->info('deleting: '.$sc->fullname);
            Pwdinfo::destroy($sc->id);
          }
        }
      } else {
        $this->info('Updating PWDPAX on INVHDR: '.$invhdr->srefno());
        $invhdr->pwdpax = $lineno;
        $invhdr->save();
      }

    } else {
      $this->info('No problem found!');
    }
  }

  private function fixSenior($invhdr) {
    $this->info(' ');
    $this->info('SCINFO:');
    
    $this->info($invhdr->scpax);

    $lineno = count($invhdr->scinfos);
    

    if ($invhdr->scpax > $lineno) {
      //
      $this->info('CASE 1');
    } elseif ($invhdr->scpax < $lineno) {
      
      $this->info('CASE 2');
      
      if ($invhdr->scdisc<=0 && $invhdr->scdisc2<=0) {
        foreach ($invhdr->scinfos as $key => $sc) {
          $this->info(($key+1).' '.$sc->fullname);      
          if (empty($sc->refno)) {          
            $this->info('deleting: '.$sc->fullname);
            Scinfo::destroy($sc->id);
          }
        }
      } else {
        $this->info('Updating SCPAX on INVHDR: '.$invhdr->srefno());
        $invhdr->scpax = $lineno;
        $invhdr->save();
      }

    } else {
      $this->info('No problem found!');
    }
  }

  private function fixInvdtl($invhdr) {
  
    $lineno = 0;
    $this->info(' ');
    $this->info('ORDERHDR:');

    DB::beginTransaction();

    foreach ($invhdr->orderhdrs as $okey => $orderhdr) {
      $cancel = $orderhdr->cancelled ? '- OS is cancelled' : 'OS is OK!';
      $this->info($orderhdr->refno.': '.$cancel);
      foreach ($orderhdr->orderdtls as $dkey => $orderdtl) {
        $this->info(' '.($dkey+1).' '.rpad($orderdtl->product->shortdesc,25).' '
          .lpad(nf($orderdtl->qty), 6).' '
          .lpad(nf($orderdtl->unitprice), 8).' '
          .lpad(nf($orderdtl->amount), 10)
        );

        if ($orderhdr->cancelled) {
          //$this->info('Skipping this OS');
        } else {
          //$this->info('Importing to INVDTL');

          try {
            $this->insertToInvdtl($invhdr, $orderhdr, $orderdtl, $lineno);
          } catch (Exception $e) {
            DB::rollback();
            $this->error('Error: '. $e->getMessage());
            exit;
          }
          $lineno++;
        }
      }
    }

    $invhdr->totinvline = $lineno;
    $invhdr->save();

    DB::commit();
  }


  private function insertToInvdtl($invhdr, $orderhdr, $orderdtl, $lineno) {

    $attr = [
      'invhdrid'  => $invhdr->id,
      'productid' => $orderdtl->productid,
      'vatcode'   => $orderdtl->vatcode,
      'qty'       => $orderdtl->qty,
      'unitprice' => $orderdtl->unitprice,
      'amount'    => $orderdtl->amount,
      'avecost'   => 0,
      'notes'     => $orderdtl->notes,
      'iscombo'   => $orderdtl->iscombo,
      'combotext' => $orderdtl->combotext,
      //'status'    => ,
      //'cancelled' => ,
      //'cancelid'  => ,
      'lineno'    => $lineno,
    ];

    return $this->invdtl->create($attr);

    //$this->info(json_encode($attr));
  }
}
