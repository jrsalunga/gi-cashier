<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use Exception;
use App\Repositories\TimelogRepository as Timelog;
use App\Repositories\BackupRepository as BackupRepo;
use DB;

class DashboardController extends Controller 
{
	public $timelog;
	protected $backup;

	public function __construct(Timelog $timelog, BackupRepo $backup){

		$this->timelog = $timelog;
		$this->backup = $backup;
		
	}

	public function getIndex(Request $request) {

		$backups = $this->backup->dailyLogs(7);
    $inadequates = $this->backup->inadequateBackups();
    //$inadequates = null;
		/*
		$backup = Backup::where('branchid', $request->user()->branchid)
											->orderBy('uploaddate', 'DESC')
											->first();
											*/
		//return $backup->uploaddate->diffForHumans(Carbon::now());

		return view('dashboard')->with('backups', $backups)->with('inadequates', $inadequates);
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


  public function pepsi(Request $request) {
  	/*
  	$components = ['062BFFD1637011E5B83800FF59FBB323',
  								'063278FF637011E5B83800FF59FBB323',
  								'0631FF85637011E5B83800FF59FBB323',
  								'062C796C637011E5B83800FF59FBB323',
  								'0615D0AA637011E5B83800FF59FBB323',
  								'06163FD0637011E5B83800FF59FBB323'];
  								*/
  	$components = [
  								'08BA6275637011E5B83800FF59FBB323',  // 7up
  								'08BABBC7637011E5B83800FF59FBB323',		
  								'08BA0B96637011E5B83800FF59FBB323',		
  								'061BC1EB637011E5B83800FF59FBB323',		
  								'06229D04637011E5B83800FF59FBB323',		
  								'06232D26637011E5B83800FF59FBB323',		
  								'08BD2EDD637011E5B83800FF59FBB323',		
  								'08BD8844637011E5B83800FF59FBB323',		
  								'08BDE48E637011E5B83800FF59FBB323',	
  								'08BE401B637011E5B83800FF59FBB323',		
  								'08BE97FC637011E5B83800FF59FBB323',		
  								'08BEEB6C637011E5B83800FF59FBB323',
  								'062BFFD1637011E5B83800FF59FBB323',  // pepsi reg
  								'063278FF637011E5B83800FF59FBB323',  // pepsi max
  								'0631FF85637011E5B83800FF59FBB323',  // pepsi light
  								'FCF22339346F11E6B8E2CFB10D2BAFEE',  // pepsi zero
  								'062C796C637011E5B83800FF59FBB323',  // pepsi 500
  								'0615D0AA637011E5B83800FF59FBB323',  // iced tea
  								'06163FD0637011E5B83800FF59FBB323',  // tea
  								];  

  	$data = [];

  	$branches = \App\Models\Branch::orderBy('code')->get();

  	if($request->input('year')!='' && $request->input('branchid')!='') {

	  	foreach ($components as $key => $value) {
	  		$date = \Carbon\Carbon::parse($request->input('year').'-01-01');

	  		$results = \App\Models\Purchase2::select(DB::raw('date, SUM(qty) AS qty, SUM(tcost) AS tcost'))
	  							->where('componentid', $value)
	  							->where('branchid', $request->input('branchid'))
	  							->where(DB::raw('YEAR(date)'), $request->input('year'))
	  							->groupBy(DB::raw('YEAR(date)'))
	  							->groupBy(DB::raw('MONTH(date)'))
	  							->get();


	  		for ($i=0; $i < 12; $i++) { 

	  			$filtered = $results->filter(function ($item) use ($date){
		          return $item->date->format('Y-m') == $date->format('Y-m')
		                ? $item : null;
		      });

	  			$data[$key][$date->format('Y-m-d')] = $filtered->first();
	  			$date->addMonth();
	  		}
	  	}
  	}

  	if($request->input('data')!='')
  		return $data;

  	return view('blank')->with('branches', $branches)->with('data', $data);
  }
}