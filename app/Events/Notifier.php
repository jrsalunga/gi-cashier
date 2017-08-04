<?php namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Http\Request;

// to debug .env queue=database & mysql=job table
class Notifier extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $message;

  /**
   * Create a new event instance.
   *
   * @return void
   */

  public function __construct($message)
  {
    $this->message = $message;
  }

  /**
   * Get the channels the event should be broadcast on.
   *
   * @return array
   */
  public function broadcastOn()
  {
    return ['gi.cashier'];
  }

  public function broadcastWith()
  {
    return [
      'title'=>'Notifier', 
      'message'=> $this->message
    ];
  }
}
