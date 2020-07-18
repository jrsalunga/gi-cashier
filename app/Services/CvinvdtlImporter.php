<?php namespace App\Services;
use DB;
use Carbon\Carbon;
use App\Repositories\CvinvdtlRepository as Cvinvdtl;

class CvinvdtlImporter{

  protected $cvinvdtl;

  public function __construct(Cvinvdtl $cvinvdtl) {
    $this->cvinvdtl = $cvinvdtl;
  }

  // $path - the full path including the file *DT.DBF
  public function import($branchid, Carbon $date, $path, $cmd=NULL) {

    if (!is_null($cmd)) {
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
      $cmd->line($path);
    }

    // $dbf_file = $path.DS.'CSH_AUDT.DBF';
    $dbf_file = $path;
    
    $trans = 0;

    
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $curr_date = null;

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        $data = $this->cvinvdtl->associateAttributes($row);
        $data['branch_id'] = $branchid;

        // try {
        //   $vfpdate = vfpdate_to_carbon(trim($row['PODATE']));
        // } catch(Exception $e) {
        //   // log on error
        //   continue;
        // }


        //   if (!is_null($cmd))
        //     $cmd->info('delete: '. $data['date']);
        // $this->cvinvdtl->deleteWhere(['branch_id'=>$branchid, 'cvdate'=>$vfpdate->format('Y-m-d')]);


        if (!is_null($cmd))
          $cmd->info('import: '. $data['invdate']);
        // $this->cashAudit->findOrNew($data, ['date', 'branch_id']);
        
        // $this->cvinvdtl->firstOrNewField($data, ['cvdate', 'branch_id']);
        if(!is_null($this->cvinvdtl->verifyAndCreate($data)));
          $trans++;
          
      } // end: for

      dbase_close($db);
      unset($ds);
    } else {
      if (!is_null($cmd))
        $cmd->info('********* file not exist ******************');
    } // end: file_exists

    return $trans;
  }
}



