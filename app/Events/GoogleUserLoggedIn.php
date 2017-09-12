<?php namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Http\Request;

class GoogleUserLoggedIn extends Event implements ShouldBroadcast
{
    use SerializesModels;
    public $email;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($email, $avatar)
    {
        $this->email = $email;
        $this->avatar = $avatar;
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
      'icon'=> $this->avatar,
      'title'=>'Cashier\'s Module', 
      'message'=> $this->email.' successfully logged in.'
    ];
  }
}
