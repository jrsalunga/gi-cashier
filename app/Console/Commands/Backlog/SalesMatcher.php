<?php namespace App\Console\Commands\Backlog;

use DB;
use App\Models\Process;
use Illuminate\Console\Command;
use Carbon\Carbon;

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
  protected $description = 'match';

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
          //->where('dailysales.date', '>=', '2017-04-01')
          ->where('dailysales.date', '>=', '2016-12-01')
          ->where('dailysales.sales', '<>', DB::raw('dailysales.chrg_total'))
          ->where('dailysales.chrg_total', '>', 0) // or >=0
          ->orderBy('branch.code')
          ->get();
    
    
    $this->info(' '.count($rs).' ');

    foreach ($rs as $key => $ds) {
      $this->info(' '.($key+1).' '.$ds->code.' '.$ds->date.' '.$ds->chrg_total.' '.$ds->sales);



      $proc = Process::where('filedate', $ds->date)->where('code', $ds->code)->first();
      
      if (is_null($proc)) {

        $d = Carbon::parse($ds->date);
        $file = 'GC'.$d->copy()->addDay()->format('mdy').'.ZIP';
        
        $attrs = [
          'filename'  => $file,
          'filedate'  => $ds->date,
          'code'      => $ds->code,
          'path'      => DS.$ds->code.DS.$d->format('Y').DS.$d->format('m').DS.$file,
          'processed' => 0
        ];

        $n = Process::create($attrs);

        is_null($n) ? $this->info(' not saved! ') : $this->info(' saved! ');
      } else {
        $this->info(' has record on for_process');
      } 

    }
    


    $this->info(' end...');
    //$this->info(print_r(DB::getQueryLog()));


  }
}