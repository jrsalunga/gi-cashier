<?php namespace App\Events\Process;
use Carbon\Carbon;
use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AggregatorMonthly extends Event 
//class AggregateComponentMonthly extends Event implements ShouldBroadcast
{
  use SerializesModels;

  public $table;
  public $date;
  public $branchid;
  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct($table, Carbon $date, $branchid)
  {
    $this->date = $date;
    $this->branchid = $branchid;
    $this->table = $table;
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
      'message'=> 'Processing Aggregator Monthly'
    ];
  }
}
