<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use Exception;
use App\Repositories\TimelogRepository as Timelog;

class DashboardController extends Controller 
{
	public $timelog;

	public function __construct(Timelog $timelog){

		$this->timelog = $timelog;
	
	}

	public function getIndex(Request $request) {

		$backup = Backup::where('branchid', $request->user()->branchid)
											->orderBy('uploaddate', 'DESC')
											->first();
		//return $backup->uploaddate->diffForHumans(Carbon::now());

		return view('dashboard')->with('backup', $backup);
	}


	public function getDailyDTR(Request $request) {


		$date = carbonCheckorNow($request->input('date'));


		$this->timelog->whereBetween('datetime', 
				[$date->format('Y-m-d').' 06:00:00', $date->copy()->addDay()->format('Y-m-d').' 05:59:59']
			)->get();

		return view('dashboard')->with('backup', $backup=null);
	}
}