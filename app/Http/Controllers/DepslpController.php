<?php namespace App\Http\Controllers;

use StdClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\DateRange;
use App\Http\Controllers\Controller;
use App\Repositories\DepslipRepository as DepslpRepo;



class DepslpController extends Controller { 


	protected $depslip;


	public function __construct(DepslpRepo $depslip) {
		$this->depslip = $depslip;

	}



	public function getHistory($brcode, Request $request) {

		
		$depslips = $this->depslip
			//->skipCache()
			->with(['fileUpload'=>function($query){
        $query->select(['filename', 'terminal', 'id']);
      }])
      ->orderBy('created_at', 'DESC')
      ->paginate(10);
      //->all();
				
		return view('docu.depslp.index')->with('depslips', $depslips);
	}


	public function getChecklist($brcode, Request $request) {
		$date = carbonCheckorNow($request->input('date'));
		return view('docu.depslp.checklist')->with('date', $date);
	}






}