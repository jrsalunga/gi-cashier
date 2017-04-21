<?php namespace App\Console\Commands\Backlog;

use DB;
use App\Helpers\Process;
use Illuminate\Console\Command;


class SalesMatcher extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ds:matcher';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process dailysales backlog';

  /**
   * Create a new command instance.
   *
   * @return void
  **/

  public function __construct()
  {
    parent::__construct();
    
  }

  public function handle() {

    $this->info(' starting...');
    //DB::enableQueryLog();

    /*
    $rs = DB::table('dailysales')
            ->join('hr.branch', function ($join) {
              $join->on('branch.id', '=', 'dailysales.branchid');
              //->where('dailysales.chrg_total', '>=', 0);
                
          })
              ->where('dailysales.date', '>=', '2017-01-01')
              ->where('dailysales.sales', '<>', DB::raw('dailysales.chrg_total'))
          ->get();
          */


            
    $rs = DB::table('dailysales')
          ->select(DB::raw('branch.code, dailysales.date, dailysales.sales, dailysales.chrg_total'))
          ->leftJoin('hr.branch', 'hr.branch.id', '=', 'dailysales.branchid')
          ->where('dailysales.date', '>=', '2017-01-01')
          ->where('dailysales.sales', '<>', DB::raw('dailysales.chrg_total'))
          ->where('dailysales.chrg_total', '>', 0) // or >=0
          ->orderBy('branch.code')
          ->get();
    
    
    $this->info(' '.count($rs).' ');

    foreach ($rs as $key => $ds) 
      $this->info(' '.($key+1).' '.$ds->code.' '.$ds->date.' '.$ds->chrg_total.' '.$ds->sales);
    


    $this->info(' end...');
    //$this->info(print_r(DB::getQueryLog()));


  }
}