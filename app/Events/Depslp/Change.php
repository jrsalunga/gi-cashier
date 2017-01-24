<?php namespace App\Events\Depslp;


use App\Events\Event;
use App\Models\Depslip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class Change extends Event 
{
  use SerializesModels;
  public $old_depslp;
  public $new_depslp;
  public $change;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Depslip $old_depslp, Depslip $new_depslp, $change)
  {
    $this->old_depslp = $old_depslp;
    $this->new_depslp = $new_depslp;
    $this->change = $change;
  }
  /**
   * Get the channels the event should be broadcast on.
   *
   * @return array
   */
  public function broadcastOn()
  {

  }

  
}
