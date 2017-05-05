<?php namespace App\Events\Timelog;

use App\Events\Event;
use App\Models\Timelog as TimelogModel;
use App\Models\Employee;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class Timelog extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $timelog;
  public $employee;
  public $brcode;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  //public function __construct($timelog=null, $employee=null)
  public function __construct(TimelogModel $timelog, Employee $employee)
  {
    $this->timelog = $timelog;
    $this->employee = $employee;
    $this->brcode = session('user.branchcode');
  }

  /**
   * Get the channels the event should be broadcast on.
   *
   * @return array
   */
  public function broadcastOn()
  {
    return ['gi.timelog'];
  }

  public function broadcastWith()
  {

    $title = $this->employee->lastname.', '.$this->employee->firstname;
    $icon = $this->employee->hasPhoto() 
      ? 'http://cashier.giligansrestaurant.com/images/employees/'.$this->employee->code.'.jpg'
      : 'http://cashier.giligansrestaurant.com/images/login-avatar.png';

    $message = $this->employee->firstname.' ('.$this->employee->position->descriptor.')'; 

    return [
      'title'=>$title, 
      'message'=> $message,
      'icon'=> $icon
    ];
  }
}
