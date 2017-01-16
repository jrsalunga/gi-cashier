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
  }

  /**
   * Get the channels the event should be broadcast on.
   *
   * @return array
   */
  public function broadcastOn()
  {
    return ['gi.backup'];
  }

  public function broadcastWith()
  {
    return [
      'title'=>'Cashier\'s Module', 
      'message'=> $this->status 
        ? $this->user->name.' uploaded Deposit Slip: ' .$this->depslp->filename.' with '.number_format($this->depslp->amount,2).' - '.$this->depslp->cashier
        : $this->user->name.' error on uploading Deposit Slip '.$this->depslp->cashier
    ];
  }
}
