<?php namespace App\Console\Commands\Backlog;

use Illuminate\Console\Command;
use App\Repositories\DailySalesRepository as DSRepo;
use App\Repositories\SalesmtdRepository as SalesRepo;
use App\Models\DailySales;

class OpenCloseDate extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'process:openclosedate {--date= : YYYY-MM-DD}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process open/close date';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  protected $sales;
  protected $ds;

  public function __construct(DSRepo $ds, SalesRepo $sales)
  {
    parent::__construct();
    $this->sales = $sales;
    $this->ds = $ds;
  }

  public function handle() {

    $date = is_null($this->option('date')) 
      ? $this->ask('Enter date YYYY-MM-DD format')
      : $this->option('date');
  
    if (!is_iso_date($date)) {
      
      if ($date==='all') {
          $this->comment('Will proccess all DailySales.');
          $dss = DailySales::with(['branch'=>function($query){
            $query->select(['code', 'descriptor', 'id']);
          }])->all();
      } else if (is_year(explode('-',$date)[0]) && is_month(explode('-',$date)[1])) {
          $d = c($date.'-01');
          $this->comment('Will proccess by year month of '. $date.'-01 - '.$d->copy()->endOfMonth()->format('Y-m-d'));
          $dss = DailySales::with(['branch'=>function($query){
            $query->select(['code', 'descriptor', 'id']);
          }])->whereBetween('date', [$date.'-01', $d->copy()->endOfMonth()->format('Y-m-d')])->get();
      } else {
          $this->info('Invalid date format.');
          exit;
      }
      
    } else {
        $dss = DailySales::with(['branch'=>function($query){
            $query->select(['code', 'descriptor', 'id']);
          }])->where('date', $date)->get();
      $this->comment($date .' will proccess all DailySales.');
    }

    

    if ($dss->isEmpty()) {
      $this->info('No report found on this date.');
      exit;
    }


    foreach ($dss as $key => $ds) {

      $this->comment('Tying to update '.$ds->branch->code.' - '. $ds->date->format('Y-m-d'));
      

      $first_sales = $this->sales->skipCache()->orderBy('ordtime')->findWhere(['branch_id'=>$ds->branchid, 'orddate'=>$ds->date->format('Y-m-d')]);
     
      $last_sales = $this->sales->skipCache()->orderBy('ordtime', 'desc')->findWhere(['branch_id'=>$ds->branchid, 'orddate'=>$ds->date->format('Y-m-d')]);

      if ($first_sales->isEmpty() || $last_sales->isEmpty()) {
        $this->info('Skipping update');
        $this->comment('--------------------------------------');
        continue;
      }

      //return dd(count($first_sales));
        
     $this->comment($first_sales[0]->ordtime);
     $this->comment($last_sales[0]->ordtime);

      $d = DailySales::where('date', $ds->date->format('Y-m-d'))
        ->where('branchid', $ds->branchid)
        ->update(['opened_at' => $first_sales[0]->ordtime, 'closed_at'=>$last_sales[0]->ordtime]);

     
      if ($d)
        $this->info('Updated!');
      else
        $this->info('No Update!');

      $this->comment('--------------------------------------');
    } 

      
  

  }




}