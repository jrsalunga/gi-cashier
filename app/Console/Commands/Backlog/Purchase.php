<?php namespace App\Console\Commands\Backlog;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use App\Models\DailySales as DS;
use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\Purchase2Repository as PurchRepo;
use App\Repositories\DateRange;


class Purchase extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'backlog:purch {brcode : Branch Code} {date : YYYY-MM-DD}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process Purchase';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $purchase;
  protected $ds;
  protected $dr;

  public function __construct(DSRepo $ds, PurchRepo $purchase, DateRange $dr)
  {
    parent::__construct();
    $this->ds = $ds;
    $this->dr = $dr;
    $this->purchase = $purchase;
  }

  public function handle() {

    $month = false;
    $year = false;
  	$br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
  	if (!$br) {
      $this->info('Invalid Branch Code.');
      exit;
    }

    $date = $this->argument('date');

    if (is_iso_date($date)) {
      $this->info('is iso date');
      $date = c($date);
    } else if (is_year(explode('-',$date)[0]) && is_month(explode('-',$date)[1])) {
      $this->info('month date');
      $month = true;
      $date = c($date.'-01');
      $this->dr->date = $date->format('Y-m-d');
    } elseif ($date==='all')
      $this->info('all');
      $year = true;
      $date = c('2017-01-01');
    } else {
      $this->info('Invalid date');
      exit;
    }

    $this->info('Starting to process...');
      
    //$this->info($date);


    if ($month) {
      $eom = $date->copy();
      do {
        //$this->info($date);
        $this->saveFoodCost($date, $br);        
      } while ($date->addDay() < $eom->endOfMonth());
    } elseif ($year) {
      $eom = c('2017-06-04');
      do {
        $this->saveFoodCost($date, $br);        
      } while ($date->addDay() < $eom->endOfMonth());
    } else {
      $this->saveFoodCost($date, $br);        
    }


    exit;


  }

  private function saveFoodCost(Carbon $date, $br) {
    $purchase = $this->getFoodCost($date, $br->id);
    if ($purchase) {
      //$this->info($purchase);
      $res = $this->setFoodCost($date, $br->id, $purchase->tcost);
      if ($res) {
        $this->info($br->code.' '. $date->format('Y-m-d') .' saved! tcost:'. $purchase->tcost);
        return true;
      } else {
        $this->info($br->code.' '. $date->format('Y-m-d') .' not saved! tcost:'. $purchase->tcost);
        return false;
      }
    }
    return false;
  }


  private function getFoodCost(Carbon $date, $branchid) {
    $purchase = $this->purchase->getFoodCost($date, $branchid);
    $purchase = $purchase->first();

    if (is_null($purchase->tcost))
      return NULL;
    else
     return $purchase;
  }


  private function setFoodCost(Carbon $date, $branchid, $food_cost) {


    //$d =  $this->ds->findWhere(['branchid'=>$branchid, 
    $d =  DS::where(['branchid'=>$branchid, 
                                'date'=>$date->format('Y-m-d')],
                                ['sales'])->first();

    $cospct = ($d->sales=='0.00' || $d->sales=='0') ? 0 : ($food_cost/$d->sales)*100;


    $d->cos = $food_cost;
    $d->cospct = $cospct;

    DB::beginTransaction();
    try {
      $res = $d->save();
    } catch (Exception $e) {
      DB::rollback();
      return false;
    }

    DB::commit();

    return $res;


    /*
    return $this->ds->firstOrNew(['branchid'=>$branchid, 
                      'date'=>$date->format('Y-m-d'),
                      'cos'=> $food_cost,
                      'cospct'=> $cospct,
                      'purchcost'=>$tot_purchase],
                      ['date', 'branchid']);
    */
  }




 




}