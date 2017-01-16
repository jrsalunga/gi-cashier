<?php namespace App\Events\Upload;


use App\Events\Event;
use App\Models\Depslip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class Depslp extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $depslp;
  public $user;
  public $msg;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Depslip $depslp, $status=true)
  {
    $this->depslp = $depslp;
    $this->user = request()->user();
    $this->status = $status;

    $this->msg = $this->status 
        ? 'Deposit Slip: ' .$this->depslp->filename.' uploaded with '.number_format($this->depslp->amount,2)
        : 'Error on uploading Deposit Slip.';
  }
  /**
   * Get the channels the event should be broadcast on.
   *
   * @return array
   */
  public function broadcastOn()
  {
    return ['gi.upload'];
  }

  public function broadcastWith()
  {
    return [
      'title'=> $this->user->name.': '. $this->depslp->cashier, 
      'message'=> $this->msg
    ];
  }
}
