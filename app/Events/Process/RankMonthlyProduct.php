<?php namespace App\Events\Process;
use Carbon\Carbon;
use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RankMonthlyProduct extends Event 
//class AggregateMonthlyExpense extends Event implements ShouldBroadcast
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
    return ['gi.processes'];
  }

  public function broadcastWith()
  {
    return [
      'title'=>'Notify', 
      'message'=> 'Processing RankMonthlyProduct'
    ];
  }
}
