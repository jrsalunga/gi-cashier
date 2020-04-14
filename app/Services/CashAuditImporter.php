<?php namespace App\Services;

use Carbon\Carbon;
use App\Repositories\CashAuditRepository as CashAudit;

class CashAuditImporter {

  protected $cashAudit;

  public function __construct(CashAudit $cashAudit) {
    $this->cashAudit = $cashAudit;
  }

  public function import($branchid, Carbon $date, $path, $cmd=NULL) {

    if (!is_null($cmd)) {
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
      $cmd->line($path);
    }

    $dbf_file = $path.DS.'CSH_AUDT.DBF';
    
    $trans = 0;
    
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $curr_date = null;

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $data = $this->cashAudit->associateAttributes($row);
        $data['branch_id'] = $branchid;

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['TRANDATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

        if ($vfpdate->format('Y-m-d')==$date->format('Y-m-d')) {

          if (!is_null($cmd))
            $cmd->info('delete: '. $data['date']);
          $this->cashAudit->deleteWhere(['branch_id'=>$branchid, 'date'=>$vfpdate->format('Y-m-d')]);


          if (!is_null($cmd))
            $cmd->info('import: '. $data['date']);
          // $this->cashAudit->findOrNew($data, ['date', 'branch_id']);
          $this->cashAudit->firstOrNewField($data, ['date', 'branch_id']);
          
          $trans++;
        } 
      } // end: for

      dbase_close($db);
      unset($ds);
    } else {
      // end: file_exists
      if (!is_null($cmd))
        $cmd->info('********* file not exist ******************');
    }

    return $trans;
  }


   public function importByDr($branchid, Carbon $from, Carbon $to, $path, $cmd=NULL) {

    // if (!is_null($cmd))
    //   $cmd->info('CashAuditImporter:importByDr');

    $dbf_file = $path.DS.'CSH_AUDT.DBF';
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;

      $ds['branchid'] = $branchid;
      // $branchcode = $this->getBackupCode();

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $data = $this->cashAudit->associateAttributes($row);
        $data['branch_id'] = $branchid;

        try {
          $vfpdate = vfpdate_to_carbon(trim($row['TRANDATE']));
        } catch(Exception $e) {
          // log on error
          continue;
        }

        // if (!is_null($cmd))
          // $cmd->info($vfpdate->format('Y-m-d').'<'.$from->format('Y-m-d').' && '.$vfpdate->format('Y-m-d').'>'.$to->format('Y-m-d'));

        //if ($from->gte($vfpdate) && $to->lte($vfpdate)) {
        if ($vfpdate->gte($from) && $vfpdate->lte($to)) {
          // $c->info($vfpdate);

          try {
            // $c->info('del: '.$vfpdate->format('Y-m-d'));
            $this->cashAudit->deleteWhere(['branch_id'=>$branchid, 'date'=>$vfpdate->format('Y-m-d')]);
          } catch(Exception $e) {
            dbase_close($db);
            throw new \Exception("backlogCashAudit2:delete: ".$e->getMessage());    
          }

          try {
            // $c->info('save: '.$vfpdate->format('Y-m-d'));
            $this->cashAudit->firstOrNewField($data, ['date', 'branch_id']);
          } catch(Exception $e) {
            dbase_close($db);
            throw new \Exception("backlogCashAudit2:save: ".$e->getMessage());    
          }

          $update++;
        } else {
          // if (!is_null($cmd))
          //   $cmd->error($vfpdate);
        }
      }

      if (!is_null($cmd))
        $cmd->info('Cash Audit 2 Transactions: '.$update);

      dbase_close($db);
      unset($db);
      return $update ? true:false;
    }
    return false;


  }
}



