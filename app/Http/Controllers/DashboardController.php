<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use Exception;

class DashboardController extends Controller 
{

	


	


	public function getIndex(Request $request) {

		$backup = Backup::where('branchid', $request->user()->branchid)
											->orderBy('uploaddate', 'DESC')
											->first();
		//return $backup->uploaddate->diffForHumans(Carbon::now());

		return view('dashboard')->with('backup', $backup);
	}
}