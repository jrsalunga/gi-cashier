<?php namespace App\Services;

use Carbon\Carbon;
use App\Repositories\KitlogRepository as Kitlog;

class KitlogImporter {

  protected $kitlog;

  public function __construct(Kitlog $kitlog) {
    $this->kitlog = $kitlog;
  }

  public function import($branchid, Carbon $date, $path, $cmd=NULL) {

    if (!is_null($cmd)) {
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
      $cmd->line($path);
    }

    $dbf_file = $path.DS.$date->format('Ymd').'.LOG';
    
    $trans = 0;
    
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $curr_date = null;

      //$branchcode = $this->getBackupCode();

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $data = $this->kitlog->associateAttributes($row);
        $data['branch_id'] = $branchid;

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['ORDDATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

        if (is_null($curr_date)) {
          $curr_date = $vfpdate;
          $trans = 0;

          try {

            if (!is_null($cmd))
              $cmd->info('del: '.$curr_date->format('Y-m-d'));

            $this->kitlog->deleteWhere(['branch_id'=>$branchid, 'date'=>$curr_date->format('Y-m-d')]);
          } catch(Exception $e) {
            dbase_close($db);
            throw $e;    
          }
        }


        if ($curr_date->eq($vfpdate)) {
          $trans++;

          if (!is_null($cmd))
            $cmd->info($trans.' - '.$data['ordno'].' - '.$curr_date->format('Y-m-d').' '.$data['time'].' - '.$data['productcode'].' - '.$data['product'].' - '.$data['prodcat'].' - '.$data['menucat']);
          
          $this->kitlog->verifyAndCreate($data, false);
        } else {
          $trans=0;

          if (!is_null($cmd))
            $cmd->info('********* import second ******************');
        }

      } // end: for

      dbase_close($db);
      unset($ds);
    } // end: file_exists
    return $trans;
  }
}



