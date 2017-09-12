<?php namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Http\Request;

class GoogleUserLoggedIn extends Event implements ShouldBroadcast
{
    use SerializesModels;
    public $email;
    public $request;
    public $avatar;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($email, $avatar)
    {
        $browser = getBrowserInfo();
        $this->avatar = $avatar;
        $this->request = request()->all();
        array_set($this->request, 'name', $email);
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
      'icon'=> $this->avatar,
      'title'=>'Cashier\'s Module', 
      'message'=> $this->request['name'].' successfully logged in at '
      .$this->request['ip'].' using '.$this->request['browser'].' on '. $this->request['platform']
    ];
  }
}
