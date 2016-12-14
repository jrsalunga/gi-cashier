<?php namespace App\Http\Controllers;

use DB;
use File;
use Html;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\CompanyRepository as CompanyRepo;

class RemittanceController extends Controller 
{

	protected $company;
    
  public function __construct(CompanyRepo $company) {
    $this->company = $company;
  }

	public function philhealthIndex(Request $request)
	{
		$companies = $this->company->all(['code','descriptor','id']);
		return view('remittance.philhealth.index', compact('companies'));
	}


	public function postUpload(Request $request)
	{
		//return dd($request->all());

		if (!$request->hasFile('file') || !$request->file('file')->isValid())
			return  redirect('/remittance/philhealth')->withErrors('File is corrupted! Try again.');

		if (strtolower($request->file->getClientOriginalExtension())!=='xls')
			return  redirect('/remittance/philhealth')->withErrors('Invalid file extension! Use .xls file extension.');


		$filename = $request->file->getClientOriginalName();
		$destinationPath = public_path('uploads'.DS.'remittance');

		$request->file->move($destinationPath, $filename);

		$file = $destinationPath.DS.$filename;

		try {
			$e = Excel::selectSheetsByIndex(0)->load($file);
		} catch (\Exception $e) {
			return redirect('/remittance/philhealth')->withErrors('Something went wrong! '. $e->getMessage());
		}

		try {
			$company = $this->company->find($request->input('company_id'));
		} catch (\Exception $e) {
			return redirect('/remittance/philhealth')->withErrors('Invalid company!');
		}

		if (is_null($company))
			return redirect('/remittance/philhealth')->withErrors('Company not found!');

		
		
		$fields = ['phealth_no', 'bracket', 'e_status', 'date_hired', 'birthdate'];
		$rs = $e->select($fields)->get();

		$data = [];
		$data[0] = ['MEM_PHIC_NO', 'MO_SAL', 'EE_STAT', 'EFF_DATE', 'DATE_OF_BIRTH'];
		foreach ($rs as $key => $row) {
			$idx = $key+1;
			
			foreach ($fields as $key => $value) {

				switch ($value) {
					case 'e_status':
						if ($row->{$value}=='NH') {
							$data[$idx][$key] = 'NH';
							
							try { 
								$h = sprintf('%s', c($row->date_hired)->format('m/d/Y'));
								//$h = c($row->date_hired)->format('Y-m-d');
							} catch(Exception $e) { 
								$h = null;
							}
							$data[$idx][$key+1] = sprintf('%s', $h);
						
						} else if ($row->{$value}=='S') {
							$data[$idx][$key] = 'S';

							try { 
								$h = sprintf('%s', c($row->date_hired)->format('m/d/Y'));
							} catch(Exception $e) { 
								$h = null;
							}
							$data[$idx][$key+1] = sprintf('%s', $h);

						} else {
							$data[$idx][$key] = sprintf('%s', 'A');
							$data[$idx][$key+1] = null;
						}
						break;
					case 'date_hired':
						break;
					case 'birthdate':

						try { 
							$dob = sprintf('%s', c($row->birthdate)->format('m/d/Y'));
						} catch(Exception $e) { 
							$dob = null;
						}
						$data[$idx][$key] = sprintf('%s', $dob);

						break;
					default:
						$data[$idx][$key] = sprintf('%s', $row->{$value});
						break;
				}
			}
		}


		$month = Carbon::parse($request->input('date'))->endOfMonth();

		$fname = 'PLH-'.strtoupper($company->code).'-'.$month->format('Ym');


		try {
			
			Excel::create($fname, function($excel) use ($fname, $data) {
				$excel->sheet($fname, function($sheet) use ($data)  {
						//array_shift($data);
		        //$sheet->fromArray($data);
						$sheet->fromArray($data, null, 'A1', false, false); // Won't auto generate heading columns
		    });
			})->store('csv', public_path('downloads'.DS.'remittance'));

		} catch (\Exception $e) {
			return redirect('/remittance/philhealth')->withErrors('Something went wrong! '. $e->getMessage());
		}
		
		$companies = $this->company->all(['code','descriptor','id']);

		$dl = public_path('downloads'.DS.'remittance').DS.$fname.'.csv';
		if (File::exists($dl)) {
			return redirect('remittance/philhealth')
						->with('companies', $companies)
						->with('dl', $fname.'.csv');
		} else {
			return redirect('remittance/philhealth')
						->with('companies', $companies)
						->withErrors('Something went wrong!');
		}



	}

	public function dl(Request $request, $dl){
    
    if(is_null($dl)){
    	throw new Http404("Error Processing Request");
    }

    $file = public_path('downloads'.DS.'remittance').DS.$dl;

    if (!File::exists($file))
    	throw new Http404("Error");

   return response()->download($file, $dl, ['Content-Type'=>'text/csv', 'Content-Disposition'=>'attachment; filename="'.$dl.'"']);

    $response = \Response::make($file, 200);
	 	$response->header('Content-Type', 'text/csv');
  	$response->header('Content-Disposition', 'attachment; filename="'.$dl.'"');

	  return $response;
  }





}