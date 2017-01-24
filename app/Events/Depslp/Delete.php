<?php namespace App\Events\Depslp;

use App\Events\Event;
use App\Models\Depslip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class Delete extends Event 
{
  use SerializesModels;
  public $depslp;


  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(array $depslp)
  {
    $this->depslp = $depslp;
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
