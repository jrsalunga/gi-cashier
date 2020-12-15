<?php namespace App\Console\Commands\Backlog;

use App\Events\Process\AggregatorMonthly;
use DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Repositories\MonthlySalesRepository as MS;
use App\Repositories\ChargesRepository as ChargesRepo;

class ChargesType extends Command {

	protected $signature = 'backlog:charges-type {date : YYYY-MM-DD} {--brcode=ALL : Branch Code} {--dateTo=NULL : YYYY-MM-DD}';

  protected $ms;

  public function __construct(MS $ms, ChargesRepo $charges) {
    parent::__construct();
    
    $this->ms = $ms;
    $this->charges = $charges;
  }

  public function handle() {

    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->error('Invalid date.');
      exit;
    }

    $date = Carbon::parse($date)->startOfMonth();
    if ($date->gte(c())) {
      $this->error('Invalid backup date. Too advance to backup.');
      exit;
    } 
    $this->date = $date;  

    $dateTo = is_iso_date($this->option('dateTo')) ? Carbon::parse($this->option('dateTo'))->endOfMonth() : $date->copy()->endOfMonth();
    $this->dateTo =  $dateTo->gt($date) && $dateTo->lte(c()) ? $dateTo : $date;
    $count = $this->dateTo->diffInMonths($this->date);


    if (strtoupper($this->option('brcode'))==='ALL')
      $ms = $this->ms->findWhereBetween('date', [$date->format('Y-m-d'), $dateTo->format('Y-m-d')]);
    else {
      $br = Branch::where('code', strtoupper($this->option('brcode')))->get(['code', 'descriptor', 'id'])->first();
      if (count($br)<=0) {
        $this->info('Invalid Branch Code.');
        exit;
      }

      $ms = $this->ms->scopeQuery(function($query) use ($br){
        return $query->where('branch_id', $br->id);
      })->findWhereBetween('date', [$date->format('Y-m-d'), $dateTo->format('Y-m-d')]);
    }

    $this->comment('Date(s): '.$this->date->format('Y-m-d').' - '.$this->dateTo->format('Y-m-d').' ('.($count+1).') - '.$this->option('brcode'));


    // $c = 1;
    // do {

      $d = $this->date->copy()->addMonths($c);
      $this->line($d->format('Y-m-d'));


      $ctr = $res = 0;
      foreach ($ms as $key => $b) {

        $this->info($b->branch_id.' - '.$b->date->format('Y-m-d').' - '.$b->sales);

        // $chg = $this->charges->aggregateChargeTypeByDr($b->date->copy()->startOfMonth(), $b->date->copy()->endOfMonth(), $b->branch_id);
        // $this->line(count($chg));
        
        if ($b->sales>0){
          event(new AggregatorMonthly('charge-type', $b->date, $b->branch_id));
          event(new AggregatorMonthly('sale-type', $b->date, $b->branch_id));
          event(new AggregatorMonthly('card-type', $b->date, $b->branch_id));
          $ctr++;
        }
        
      }
      $this->line('******************');
      $this->line($ctr.' - '.$res);
      $this->line('******************');
      

    //   $c++;
    // } while ($c <= $count); 

  }

  

  
}