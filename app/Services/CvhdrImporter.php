<?php namespace App\Services;
use DB;
use Carbon\Carbon;
use App\Repositories\CvhdrRepository as Cvhdr;

class CvhdrImporter {

  protected $cvhdr;

  public function __construct(Cvhdr $cvhdr) {
    $this->cvhdr = $cvhdr;
  }

  // $path - the full path including the file *CV.DBF
  public function import($branchid, Carbon $date, $path, $cmd=NULL) {

    if (!is_null($cmd)) {
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
      $cmd->line($path);
    }

    // $dbf_file = $path.DS.'CSH_AUDT.DBF';
    $dbf_file = $path;
    
    $trans = 0;

    // DB::beginTransaction();

    $lastcv = \App\Models\Autoinc::where('table', 'CVHDR')->first();
    $refno = $lastcv->lastnumber;
    
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);

      $update = 0;
      $curr_date = null;

      if ($recno>0) {
        for ($i=1; $i<=$recno; $i++) {

          $row = dbase_get_record_with_names($db, $i);
          $data = $this->cvhdr->associateAttributes($row);
          
          $data['branch_id'] = $branchid;
          $data['refno'] = $refno + $trans + 1;

          // try {
          //   $vfpdate = vfpdate_to_carbon(trim($row['CV_DATE']));
          // } catch(Exception $e) {
          //   // log on error
          //   continue;
          // }


          //   if (!is_null($cmd))
          //     $cmd->info('delete: '. $data['date']);
          // $this->cvhdr->deleteWhere(['branch_id'=>$branchid, 'cvdate'=>$vfpdate->format('Y-m-d')]);


          if (!is_null($cmd))
            $cmd->info('import: '. $data['cvdate']);
          // $this->cashAudit->findOrNew($data, ['date', 'branch_id']);
          
          // $this->cvhdr->firstOrNewField($data, ['cvdate', 'branch_id']);
          if(!is_null($this->cvhdr->verifyAndCreate($data)));
            $trans++;
            
        } // end: for

        $lastcv->lastnumber = $data['refno'];
        $lastcv->save();
      }

      dbase_close($db);
      unset($ds);
    } else {
      if (!is_null($cmd))
        $cmd->info('********* file not exist ******************');
    } // end: file_exists

    // DB::commit();

    return $trans;
  }
}



