<?php namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Http\Request;

// to debug .env queue=database & mysql=job table
class UserLoggedIn extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $request;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Request $request)
  {
    $browser = getBrowserInfo();
    $this->request = $request->all();
    array_set($this->request, 'name', $request->user()->name);
    array_set($this->request, 'ip', clientIP());
    array_set($this->request, 'browser', $browser['browser']);
    array_set($this->request, 'platform', $browser['platform']);
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
      'title'=>'Cashier\'s Module', 
      'message'=> $this->request['name'].' successfully logged in at '
      .$this->request['ip'].' using '.$this->request['browser'].' on '. $this->request['platform']
    ];
  }
}
