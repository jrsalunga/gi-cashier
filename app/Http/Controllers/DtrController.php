<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\TimelogRepository as TimelogRepo;

class DtrController extends Controller {

  protected $timelog;
    
  public function __construct(TimelogRepo $timelog) {
    $this->timelog = $timelog;
  }

  


}