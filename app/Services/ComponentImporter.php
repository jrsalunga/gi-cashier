<?php namespace App\Services;
use DB;
use Carbon\Carbon;
use App\Repositories\ComponentRepository as Repo;

class ComponentImporter {

  protected $repo;

  public function __construct(Repo $repo) {
    $this->repo = $repo;
  }

  public function import($branchid, Carbon $date, $path, $cmd=NULL) {

    if (!is_null($cmd)) {
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
      $cmd->line($path);
    }

    $dbf_file = $path.DS.'COMPONEN.DBF';
    
    $trans = 0;

    // DB::beginTransaction();

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);

      $update = 0;
      $curr_date = null;

      if ($recno>0) {
          
        for ($i=1; $i<=$recno; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $data = $this->repo->associateAttributes($row);

              
            if (!is_null($cmd))
              $cmd->info($trans.' '. $data['comp'].' - '.$data['unit'].' - '.$data['ucost'].' - '.$data['vat'].' - '.$data['yield_pct'].' - '.$data['supno'].' - '.$data['catname']);
          

            if(!is_null($this->repo->verifyAndCreate($data, true)));
              $trans++;

        } // end: for

        $cmd->line('trans: '. $trans);
      }

      dbase_close($db);

    } else {
      if (!is_null($cmd))
        $cmd->info('********* file not exist ******************');
      else 
        throw new Exception($dbf_file.' not found on backup!', 1);
      
    } // end: file_exists

    // DB::commit();

    return $trans;
  }
}



