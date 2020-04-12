<?php namespace App\Console\Commands\Import;

use DB;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Paymast as Pay;
use App\Models\Process;
use App\Helpers\Locator;
use App\Helpers\BackupExtractor;
use App\Events\Notifier;
use Illuminate\Console\Command;

class Paymast extends Command
{

	protected $signature = 'import:paymast {date : YYYY-MM-DD}';
  protected $description = 'import the paymast';

  public function handle() {

    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->error('Invalid date.');
      exit;
    }

    $date = Carbon::parse($date);
    if ($date->gte(c())) {
      $this->error('Invalid backup date. Too advance to backup.');
      exit;
    } 


    $dbf_file = 'D:\Giligans\PAY_MAST'.DS.$date->format('mdY').'.DBF';
    
    if (file_exists($dbf_file)) {
      $db = dbase_open($dbf_file, 0);
      $header = dbase_get_header_info($db);
      $recno = dbase_numrecords($db);
      $update = 0;
      $trans = 0;
      $curr_date = null;

      //$branchcode = $this->getBackupCode();

      Pay::where('date', $date->format('Y-m-d'))->delete();

      for ($i=1; $i<=$recno; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        
        $data = $this->associateAttributes($row);

        
        $this->info($data['manno'].' '.$data['lastname'].' '.$data['firstname'].' '.$data['dept']);

        $employee = Employee::with('statutory')->where('code', $data['manno'])->first();

        if (is_null($employee))
          $this->error('Not Found!');
        else {
          $this->line('Found!');
          if (is_null($employee->statutory))
            $this->error('Statutory not found!');
          else{
            $this->line('Statutory found! '. $employee->statutory->date_reg);
          }
        }

        Pay::firstOrCreate($data);
        

      } // end: for
    } else { // end: file_exists
      $this->info($dbf_file.' not found!');
    }
    

    


  }

  public function associateAttributes($r) {
    $row = [];

    $brcode = trim($r['BRANCH']);

    if (!empty($brcode)) {

      if ($brcode=='AFC') 
        $bid = '971077BCA54611E5955600FF59FBB323';
      else {
        $b = Branch::where('code', $brcode)->first();
        $bid = is_null($b) ? '' : $b->id;
      }

      $dept = substr(trim($r['DEPT']), 0, 3);

      $vfpdate = c(trim($r['PAY_DATE']).' 00:00:00');

      $row = [
        'manno'     => trim($r['MAN_NO']),
        'lastname'  => trim($r['LAST_NAM']),
        'firstname' => trim($r['FIRS_NAM']),
        'date'      => $vfpdate->format('Y-m-d'),
        'grspay'    => trim($r['GRS_PAY']),
        'dept'      => $dept,
        'din'       => ($dept=='DIN' || $dept=='CSH') ? 1 : 0,
        'kit'       => $dept=='KIT' ? 1 : 0,
        'oth'       => ($dept!='KIT' && $dept!='DIN' && $dept!='CSH') ? 1 : 0,
        'branch_id' => $bid,
      ];


      return $row;
    }

    return NULL;

  }
}