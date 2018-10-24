<?php namespace App\Console\Commands\Backlog;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use App\Models\DailySales as DS;
use Illuminate\Console\Command;


class FoodSales extends Command
{
  /**
   * The name and signature of the console command.
   *  php artisan backlog:ds ara gc021618.zip 2018-02-15
   * @var string
   */
  protected $signature = 'backlog:fs {fr : YYYY-MM-DD} {to : YYYY-MM-DD}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Backlog food sales';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

 
  

  public function handle() {


    $fr = $this->argument('fr');
    if (!is_iso_date($fr)) {
      $this->info('Invalid FR date.');
      exit;
    }

    $to = $this->argument('to');
    if (!is_iso_date($to)) {
      $this->info('Invalid TO date.');
      exit;
    }

    $fr = Carbon::parse($fr);
    $to = Carbon::parse($to);

    $branches = ['ABZ', 'ANG'];

    foreach ($branches as $key => $code) {
      $br = \App\Models\Branch::where('code', $code)->first();
      $this->info($br->code);
      foreach (monthInterval($fr, $to) as $date) {
        $this->info($date->endOfMonth());
        //event(new \App\Events\Backup\DailySalesSuccess2($date, $br->id));
        event(new \App\Events\Process\AggregatorMonthly('prodcat', $date, $br->id));
      }
    }


    /*

    $dss = \App\Models\DailySales::select('date', 'branchid', 'id')
              ->whereBetween('date', [$fr->format('Y-m-d'), $to->format('Y-m-d')])
              ->where('food_sales', '<', 1)
              ->where('sales', '>', 0)
              ->get();

    $prodcatid = app()->environment()=='local' ? '6270B37CBDF211E6978200FF18C615EC':'E838DA36BC3711E6856EC3CDBB4216A7';

    foreach ($dss as $key => $ds) {
      $this->info($key.': '.$ds->date->format('Y-m-d').' '.$ds->branch->code);

      $obj = \App\Models\Salesmtd::select(DB::raw('sum(salesmtd.netamt) as netamt'))
              ->join('product', 'product.id', '=', 'salesmtd.product_id')
              ->where('salesmtd.branch_id', $ds->branchid)
              ->where('salesmtd.orddate', $ds->date->format('Y-m-d'))
              ->where('product.prodcat_id', $prodcatid)
              ->first();

      if (is_null($obj)) {
        $this->info('NULL');
      } else {
        $this->info($obj->netamt);

        $new = \App\Models\DailySales::where('id', $ds->id)->update(['food_sales' => $obj->netamt]);

        event(new \App\Events\Process\AggregatorMonthly('prodcat', $ds->date, $ds->branchid));

      }
    }

    */

  }




}