<?php namespace App\Services;

use Carbon\Carbon;
use App\Repositories\Purchase2Repository as Repo;

class PurchaseImporter {

  protected $repo;

  public function __construct(Repo $repo) {
    $this->repo = $repo;
  }

  public function import($branchid, Carbon $date, $path, $cmd=NULL) {

    if (!is_null($cmd)) {
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
      $cmd->line($path);
    }

    $dbf_file = $path.DS.'PURCHASE.DBF';
    
    $trans = 0;
    
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $curr_date = null;
      $saved = false;

      //$branchcode = $this->getBackupCode();

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

        if (is_null($curr_date)) {
          $curr_date = $date;
          $trans = 0;

          try {
            if (!is_null($cmd))
              $cmd->info('del: '.$curr_date->format('Y-m-d'));

            try {
              $this->repo->deleteWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d'), 'save'=>0]);
            } catch(Exception $e) {
              throw $e;    
            }

            $saves = $this->repo->findWhere(['branchid'=>$branchid, 'date'=>$curr_date->format('Y-m-d'), 'save'=>1]);
            if (count($saves)>0)
              $saved = true;
          
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }
        }


        if ($curr_date->eq($vfpdate)) {
          $data = $this->repo->associateAttributes($row);
          $data['branchid'] = $branchid;
          $trans++;

          if (!is_null($cmd))
            $cmd->info($trans.' - '.$curr_date->format('Y-m-d').' - ' .$data['comp'].' - '.$data['catname'].' - '.$data['supname']);
          
          $this->repo->verifyAndCreate($data, $saved);
        } 

      } // end: for

      dbase_close($db);
      unset($ds);
    } // end: file_exists
    return $trans;
  }
}



