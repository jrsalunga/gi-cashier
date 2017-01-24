<?php namespace App\Http\Controllers;
use Event;
use StdClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\DateRange;
use App\Http\Controllers\Controller;
use App\Repositories\StorageRepository;
use Dflydev\ApacheMimeTypes\PhpRepository;
use App\Repositories\DepslipRepository as DepslpRepo;
use App\Events\Depslp\Change as DepslpChange;
use App\Events\Depslp\Delete as DepslpDelete;

class DepslpController extends Controller { 

	protected $depslip;

	public function __construct(DepslpRepo $depslip) {
		$this->depslip = $depslip;
		$this->files = new StorageRepository(new PhpRepository, 'files.'.app()->environment());
	}

	public function getHistory($brcode, Request $request) {
		
		$depslips = $this->depslip
			//->skipCache()
			->with(['fileUpload'=>function($query){
        $query->select(['filename', 'terminal', 'id']);
      }])
      ->orderBy('created_at', 'DESC')
      ->paginate(10);
				
		return view('docu.depslp.index')->with('depslips', $depslips);
	}

	public function getChecklist($brcode, Request $request) {

		$date = carbonCheckorNow($request->input('date'));
		$depslips = $this->depslip->monthlyLogs($date);

		return view('docu.depslp.checklist')->with('date', $date)->with('depslips', $depslips);
	}

	public function getAction($brcode, $id=null, $action=null) {
		if(!is_uuid($id) || $brcode!==strtolower(session('user.branchcode')))
			return redirect($brcode.'/depslp/log');

		if (strtolower($action)==='edit')
			return $this->editDepslp($id);
		else
			return $this->viewDepslp($id);
	}

	private function viewDepslp($id) {
		$depslp = $this->depslip->find($id);
		return view('docu.depslp.view', compact('depslp'));
	}

	private function editDepslp($id) {
		$depslp = $this->depslip->find($id);
		if($depslp->verified || $depslp->matched)
			return $this->viewDepslp($id);
		return view('docu.depslp.edit', compact('depslp'));
	}

	public function getImage(Request $request, $brcode, $filename) {



		$id = explode('.', $filename);

		if(!is_uuid($id[0]) || $brcode!==strtolower(session('user.branchcode')))
			return abort(404);

		$d = $this->depslip->find($id[0]);

		//$path = 'DEPSLP'.DS.$d->date->format('Y').DS.session('user.branchcode').DS.$d->date->format('m').DS.$d->filename;
		$path = $this->getPath($d);

		return dd($this->files->exists($this->getPath($d)));

		

		if(!$this->files->exists($this->getPath($d)))
			return abort(404);

		if($request->has('download') && $request->input('download')==='true') {
    	return response($this->files->get($path), 200)
	 						->header('Content-Type', $this->files->fileMimeType($path))
  						->header('Content-Disposition', 'attachment; filename="'.$d->filename.'"');
		}

		return response($this->files->get($path), 200)
	 						->header('Content-Type', $this->files->fileMimeType($path));

	}

	public function put(Request $request) {

		$rules = [
			'date'				=> 'required|date',
			'time'				=> 'required',
			'amount'			=> 'required',
			'cashier'			=> 'required',
			'id'					=> 'required',
		];

		$validator = app('validator')->make($request->all(), $rules);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);
		
		$old_depslip = $this->depslip->find($request->input('id'));
		if(!is_null($old_depslip)) {
			
			$d = $this->depslip->update([
				'date' 				=> request()->input('date'),
	    	'time' 				=> request()->input('time'),
	    	'amount' 			=> str_replace(",", "", request()->input('amount')),
	    	'cashier' 		=> $request->input('cashier'),
	    	'remarks' 		=> $request->input('notes'),
	    	'updated_at' 	=> c()
			], $request->input('id'));

			//Event::fire('depslp.changed', ['new'=>$old_depslip]);
			$arr = array_diff($old_depslip->toArray(), $d->toArray());
			array_forget($arr, 'updated_at');
			
			if (app()->environment()==='production')
				event(new DepslpChange($old_depslip, $d, $arr));

			return redirect(brcode().'/depslp/'.$d->lid())
							->with('alert-success', 'Deposit slip is updated!');
		}

		return redirect()->back()->withErrors('Deposit Slip not found!');
	}



	private function getPath($d) {
		return 'DEPSLP'.DS.$d->date->format('Y').DS.session('user.branchcode').DS.$d->date->format('m').DS.$d->filename;
	}

	public function delete(Request $request) {

		$validator = app('validator')->make($request->all(), ['id'=>'required'], []);

		if ($validator->fails()) 
			return redirect()->back()->withErrors($validator);

		$depslp = $this->depslip->find($request->input('id'));

		if (is_null($depslp))
			return redirect()->back()->withErrors('Deposit slip not found!');

		if (!$depslp->isDeletable())
			return redirect()->back()->withErrors($depslp->fileUpload->filename.' deposit slip is not deletable, already verified!');

		if ($this->depslip->delete($depslp->id)) {
			
			if ($this->files->exists($this->getPath($depslp)))
				$this->files->deleteFile($this->getPath($depslp));

			if (app()->environment()==='production')
				event(new DepslpDelete($depslp->toArray()));

			return redirect(brcode().'/depslp/log')
							->with('depslp.delete', $depslp)
							->with('alert-important', true);
		}
		return redirect()->back()->withErrors('Error while deleting record!');
	}






	






}