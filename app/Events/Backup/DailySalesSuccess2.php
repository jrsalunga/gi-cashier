<?php namespace App\Events\Backup;
use Carbon\Carbon;
use App\User;
use App\Events\Event;
use App\Models\Backup;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DailySalesSuccess2 extends Event 
//class DailySalesSuccess extends Event implements ShouldBroadcast
{
  use SerializesModels;

  public $date;
  public $branchid;
  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Carbon $date, $branchid)
  {
    $this->date = $date;
    $this->branchid = $branchid;
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
      'title'=>'DailySalesSuccess', 
      'message'=> 'Daily Sales Success'
    ];
  }
}
