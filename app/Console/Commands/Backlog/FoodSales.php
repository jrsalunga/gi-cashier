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






    $dss = DB::table('dailysales')
              ->select('date', 'branchid')
              ->whereBetween('date', [$fr->format('Y-m-d'), $to->format('Y-m-d')])
              ->where('food_sales', '<', 1)
              ->where('sales', '>', 0)
              ->get();


    foreach ($dss as $key => $ds) {
      $this->info($key.': '.$ds->date.' '.$ds->branchid);
    }

  }




}