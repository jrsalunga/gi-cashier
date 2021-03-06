<?php namespace App\Repositories;
use DB;
use Carbon\Carbon;
use App\Helpers\Locator;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use App\Repositories\Criterias\ByBranch;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Models\Branch;

//class BackupRepository extends BaseRepository implements CacheableInterface
class BackupRepository extends BaseRepository 
{
  protected $cacheMinutes = 5;
  protected $locator;
  
  //use CacheableRepository;

  public function boot(){
    $this->pushCriteria(new ByBranch(request()));
  }

  public function __construct() {
    parent::__construct(app());

    $this->locator = new Locator('pos');
      
  }


  public function model() {
    return 'App\\Models\\Backup';
  }


  public function dailyLogs($days = 7) {
    $arr = [];
    $to = Carbon::now();
    //$to = Carbon::parse('2016-05-31');
    $fr = $to->copy()->subDays($days);

    $data = $this->aggregateDailyLogsProcessed($fr, $to);


    for ($i=0; $i < $days; $i++) { 

      $date = $to->copy()->subDays($i);

      $filtered = $data->filter(function ($item) use ($date){
        return $item->filedate->format('Y-m-d') == $date->format('Y-m-d')
          ? $item : null;
      });

      array_push($arr, [ 
          'date'=>$date,
          'backup'=>$filtered->first()]
      );
    }
    /*
    do {
      
      
      
      array_push($arr, [ 
          'date'=>$date,
          'backup'=>$filtered->first()]
      );

    } while ($date->addDay() < $to);
    */
    
    return $arr;
  }

  private function aggregateDailyLogsProcessed(Carbon $fr, Carbon $to) {
    return $this->scopeQuery(function($query) use ($fr, $to) {
      return $query
                ->select(DB::raw('backup.*, count(backup.id) as count, dailysales.sales, dailysales.slsmtd_totgrs'))
                ->leftJoin('dailysales', function($join) {
                    $join->on('dailysales.branchid', '=', 'backup.branchid')
                        ->on('dailysales.date', '=', DB::raw('DATE_FORMAT(backup.filedate, "%Y-%m-%d")'));
                  })
                ->whereBetween('backup.filedate', 
                  [$fr->format('Y-m-d').' 00:00:00', $to->format('Y-m-d').' 23:59:59']
                  )
                ->where('backup.processed', '1')
                ->groupBy(DB::raw('DAY(backup.filedate)'))
                ->orderBy('backup.uploaddate', 'DESC');
                //->orderBy('filedate', 'DESC');
    })->all();
  }



  private function aggregateDailyLogs(Carbon $fr, Carbon $to) {
    return $this->scopeQuery(function($query) use ($fr, $to) {
      return $query
                ->select(DB::raw('*, count(*) as count'))
                ->whereBetween('filedate', 
                  [$fr->format('Y-m-d').' 00:00:00', $to->format('Y-m-d').' 23:59:59']
                  )
                ->groupBy(DB::raw('DAY(filedate)'))
                ->orderBy('uploaddate', 'DESC');
                //->orderBy('filedate', 'DESC');
    })->all();
  }



  public function monthlyLogs(Carbon $date) {
    $arr = [];
    $fr = $date->firstOfMonth();
    $to = $date->copy()->lastOfMonth();
    $m = false;
    $b = NULL;
    $e = false;
    
    
    $data = $this->aggregateDailyLogs($fr, $to);
    $d = new DailySales2Repository;

    $dss = $d->findWhere([['date','like', $date->format('Y-m-').'%']], ['date', 'sales', 'chrg_total', 'chrg_othr']);

    for ($i=0; $i < $date->daysInMonth; $i++) { 

      $date = $fr->copy()->addDays($i);

      $filtered = $data->filter(function ($item) use ($date){
        return $item->filedate->format('Y-m-d') == $date->format('Y-m-d')
          ? $item : null;
      });

      $b = $filtered->first();

      if(!is_null($b))
        $e = file_exists(config('gi-dtr.upload_path.pos.'.app()->environment()).session('user.branchcode').DS.$b->year.DS.$b->filedate->format('m').DS.$b->filename);
      else
        $e = 0;

      $ds = $dss->filter(function ($item) use ($date){
        return $item->date->format('Y-m-d') == $date->format('Y-m-d')
          ? $item : null;
      });

      $ds = $ds->first();

      if(is_null($ds))
        $m = false;
      else {
        $m = number_format($ds->sales, 2, '.', '') === number_format($ds->chrg_total, 2, '.', '') ? true:false;
         if (!$m)
          $m = number_format($ds->sales, 2, '.', '') === number_format($ds->chrg_total-$ds->chrg_othr, 2, '.', '') ? true:false;
      }
      
      array_push($arr, [ 
          'date'=>$date,
          'backup'=>$b,
          'exist'=>$e,
          'match'=>$m]
      );
    }

    
    return $arr;
  }

  public function inadequateBackups($fr=null, $to=null) {
    if (is_null($fr) && is_null($to)) {
      $to = Carbon::now();
      $fr = $to->copy()->subDays(30);
    }

    $fr = Carbon::parse($fr);
    $to = Carbon::parse($to);

    if ($fr->gt($to))
      return false;


    $branch = Branch::where('code', strtoupper(substr(request()->user()->name, 0, 3)))->first();
    $arr = [];
    $o = $fr->copy();
    if ($branch && $branch->code!='GHO') {
      do {
        $path = strtoupper($branch->code).DS.$o->format('Y').DS.$o->format('m').DS.'GC'.$o->format('mdy').'.ZIP';
        if (!$this->locator->exists($path) && Carbon::parse(now())->gt($o) && Carbon::parse($branch->opendate)->lt($o))
          array_push($arr, Carbon::parse($o->format('Y-m-d').' 00:00:00'));
      } while ($o->addDay() <= $to);
    }
   
    return $arr;

  }




  
  

    




}