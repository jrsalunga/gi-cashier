<?php namespace App\Events\Posting;

use App\User;
use App\Events\Event;
use App\Models\Backup;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SalesmtdSuccess extends Event 
//class SalesmtdSuccess extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $backup;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Backup $backup)
  {
    $this->backup = $backup;
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
