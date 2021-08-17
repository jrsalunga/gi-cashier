<?php namespace App\Services;
use DB;
use Carbon\Carbon;
use App\Repositories\BegBalRepository as Repo;

class BegBalImporter {

  protected $repo;

  public function __construct(Repo $repo) {
    $this->repo = $repo;
  }

  // $path - the full path including the file BEG_BAL.DBF
  public function import($branchid, Carbon $date, $path, $cmd=NULL) {

    if (!is_null($cmd)) {
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
      $cmd->line($path);
    }

    $dbf_file = $path.DS.'BEG_BAL.DBF';
    
    $trans = 0;

    // DB::beginTransaction();

    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);

      $update = 0;
      $curr_date = null;

      if ($recno>0) {
          
        if (!is_null($cmd))
          $cmd->info('delete: '. $date);
          
        $this->repo->deleteWhere(['branch_id'=>$branchid, 'date'=>$date->format('Y-m-d')]);
        
        for ($i=1; $i<=$recno; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $data = $this->repo->associateAttributes($row);

          if ($data['qty']>0 || $data['tcost']) {
              
            if (!is_null($cmd))
              $cmd->info('saving: '. $data['comp'].' - '.$data['qty'].' * '.$data['ucost'].' = '.$data['tcost']);
          
            $data['branch_id'] = $branchid;
            $data['date'] = $date->format('Y-m-d');

            if(!is_null($this->repo->verifyAndCreate($data)));
              $trans++;
          } else {
            // if (!is_null($cmd))
            //   $cmd->line('skip: '. $data['comp']);
          }
        } // end: for

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



