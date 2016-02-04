<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Exception;

class DashboardController extends Controller 
{

	


	


	public function getIndex(Request $request) {

		return view('welcome');
	}
}