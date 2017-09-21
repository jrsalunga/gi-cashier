<?php namespace App\Console\Commands\Cron;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Process;
use Illuminate\Console\Command;
use App\Repositories\Purchase2Repository as PurchRepo;
use App\Repositories\StockTransferRepository as TransferRepo;
use App\Events\Notifier;

class BacklogCos extends Command
{

	protected $signature = 'process:backlog-cos';
  protected $description = 'process the backlog cos';
  protected $process;
  protected $transfer;
  protected $purchase;

  public function __construct(Process $process, PurchRepo $purchase, TransferRepo $transfer) {
    parent::__construct();
    $this->process = $process;
    $this->purchase = $purchase;
    $this->transfer = $transfer;
  }

  public function handle() {

    $process = $this->process
                    ->where('processed', '1')
                    ->where('type', '3')
                    ->orderBy('code')
                    ->orderBy('filedate')
                    ->first();

    if (!is_null($process)) {

	    
    	$br = Branch::where('code', strtoupper($process->code))->first();
  		if (!$br) {
  			$this->notify('Branch not found.');
  			$process->processed = 3;
  			$process->save();
	      exit;
	  	}

	  	$f = Carbon::parse($process->filedate->format('Y-m-d'))->startOfMonth();
    	$t = Carbon::parse($process->filedate->format('Y-m-d'))->endOfMonth();

      // set to know the backup is on process
      $process->processed = 5;
      $process->save();

      DB::beginTransaction();
      
      $this->info($process->code);
      foreach (dateInterval($f, $t) as $key => $date) {
        # code...
      
        $this->info($date->format('Y-m-d'));

        $ds = \App\Models\DailySales::where(['branchid'=>$br->id, 'date'=>$date->format('Y-m-d')])->first();
        
        $cos=0;
        $p = $this->purchase->getCos($br->id, $date, ["CK","FS","FV","GR","MP","RC","SS"])->all();
        if (count($p)>0) {
          $p = $p->first();
          $cos = $p->tcost;
        }

        $opex=0;
        $o = $this->purchase->getOpex($br->id, $date)->all();
        if (count($o)>0) {
          $o = $o->first();
          $opex = $o->tcost;
        }

        $transfer=0;
        $t = $this->transfer->skipCache()->getCos($br->id, $date, ["CK","FS","FV","GR","MP","RC","SS"])->all();
        if (count($t)>0) {
          $t = $t->first();
          $transfer = $t->tcost;
        }
        
        
        $this->info('DS: '.$ds->cos);
        if (number_format($cos, 2, '.','')==number_format($ds->cos, 2, '.',''))
          $this->line('same');
        else
          $this->error('not same');
        
        $this->info('Cos: '.$cos);
        $this->info('OpEx: '.$opex);
        $this->info('Trans Cos: '.$transfer);

        $ds->cos = $cos;
        $ds->opex = $opex;
        $ds->transcos = $transfer;

        if ($ds->save())
          $this->info('saved!');
        else
          $this->info('error on saving!');


      }
      
      DB::commit();

      $process->processed = 6;
      $process->save();

	    $this->info('done: '.$process->code.' '.$process->filedate.' '.$br->id);
	    
	    exit;
	    

    	$this->info(json_encode($process));
    } else {
    	$this->info('no more process');
    }

  }
  
  private function notify($msg) {
  	if(app()->environment()=='production')
      event(new Notifier('Cron\BacklogMonth: '.$msg));
    else
      $this->error($msg);
  }

  
  





}