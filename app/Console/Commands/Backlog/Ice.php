<?php namespace App\Console\Commands\Backlog;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Helpers\Locator;
use App\Models\DailySales as DS;
use Illuminate\Console\Command;
use App\Repositories\DailySales2Repository as DSRepo;
use App\Repositories\MonthlySalesRepository as MSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Repositories\PosUploadRepository as PosUploadRepo;
use App\Repositories\DateRange as DR;

class Ice extends Command
{
  /**
   * The name and signature of the console command.
   *  php artisan backlog:ds ara gc021618.zip 2018-02-15
   * @var string
   */
  protected $signature = 'backlog:ice';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Backlog process monthly sales summary with rank';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $sales;
  protected $ds;
  protected $dr;
  protected $ms;
  protected $posUploadRepo;

  public function __construct(DSRepo $ds, MSRepo $ms, SalesRepo $sales, PosUploadRepo $posUploadRepo, DR $dr)
  {
    parent::__construct();
    $this->sales = $sales;
    $this->ds = $ds;
    $this->dr = $dr;
    $this->ms = $ms;
    $this->posUploadRepo = $posUploadRepo;
  }

  public function handle() {


    $ms = $this->ms->skipCache()->scopeQuery(function($query){
            return $query
                    ->select(['date', 'branch_id'])
                    ->whereBetween('date', ['2018-01-01', '2018-10-31'])
                    ->orderBy('date');
          })->all();

    foreach ($ms as $key => $m) {
      $this->info(' ');
      $this->info(lpad($key).' '.$m->date->format('Y-m-d').' '.$m->branch_id. ' =================');

      foreach (dateInterval($m->date->copy()->startOfMonth(), $m->date) as $day => $date) {
        //$this->info(lpad($day).' '.$date->format('Y-m-d').' '.$m->branch_id);

        //event(new \App\Events\Process\AggregateMonthlyExpense($backup->date, $backup->branchid)); // recompute Monthly Expense
        //event(new \App\Events\Process\AggregatorMonthly('trans-expense', $backup->date, $backup->branchid));
      }
      //event(new \App\Events\Process\AggregateMonthlyExpense($m->date, $m->branch_id)); // recompute Monthly Expense
      event(new \App\Events\Backup\DailySalesSuccess2($m->date, $m->branch_id));
    }


  }





 




}