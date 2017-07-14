<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Exception;
use App\Repositories\EmployeeRepository;

class EmployeeController extends Controller 
{

	protected $repository;

	public function __construct(Request $request, EmployeeRepository $repository) {
		$this->repository = $repository;
	}
	

	public function search(Request $request, $param1=null) {

    $limit = empty($request->input('maxRows')) ? 10:$request->input('maxRows'); 
    $res = Employee::whereIn('branchid', [$request->user()->branchid, '971077BCA54611E5955600FF59FBB323'])
    				->where(function ($query) use ($request) {
              $query->orWhere('code', 'like', '%'.$request->input('q').'%')
          			->orWhere('lastname', 'like',  '%'.$request->input('q').'%')
		            ->orWhere('firstname', 'like',  '%'.$request->input('q').'%')
		            ->orWhere('middlename',  'like', '%'.$request->input('q').'%')
		            ->orWhere('rfid',  'like', '%'.$request->input('q').'%');
            })
            ->take($limit)
            ->get();

		return $res;
	}




	public function getByField($field, $value){
		
		$employee = Employee::with('position')->where($field, '=', $value)->first();
		
		if($employee){
			$respone = array(
						'code'=>'200',
						'status'=>'success',
						'message'=>'Hello '. $employee->firstname. '=)',
						'data'=> $employee->toArray()
			);	
			
		} else {
			$respone = array(
						'code'=>'404',
						'status'=>'danger',
						'message'=>'Invalid RFID! Record no found.',
						'data'=> ''
			);	
		}
				
		return $respone;
	} 
}