<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use Exception;
use App\Repositories\TimelogRepository as Timelog;
use App\Repositories\BackupRepository as BackupRepo;

class DashboardController extends Controller 
{
	public $timelog;
	private $backup;

	public function __construct(Timelog $timelog, BackupRepo $backup){

		$this->timelog = $timelog;
		$this->backup = $backup;
		
	}

	public function getIndex(Request $request) {

		$backups = $this->backup->dailyLogs(7);
		/*
		$backup = Backup::where('branchid', $request->user()->branchid)
											->orderBy('uploaddate', 'DESC')
											->first();
											*/
		//return $backup->uploaddate->diffForHumans(Carbon::now());

		return view('dashboard')->with('backups', $backups);
	}


	public function getDailyDTR(Request $request) {


		$date = carbonCheckorNow($request->input('date'));


		$this->timelog->whereBetween('datetime', 
				[$date->format('Y-m-d').' 06:00:00', $date->copy()->addDay()->format('Y-m-d').' 05:59:59']
			)->get();

		return view('dashboard')->with('backup', $backup=null);
	}



	public function getChecklist(Request $request) {


  	$date = carbonCheckorNow($request->input('date'));

  	$backups = $this->backup->monthlyLogs($date);
  	

  	return view('backups.checklist')
  					->with('date', $date)
  					->with('backups', $backups);
  }
}