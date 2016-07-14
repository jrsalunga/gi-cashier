<?php namespace App\Events\Backup;

use App\User;
use App\Events\Event;
use App\Models\Backup;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class ProcessSuccess extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $backup;
  public $user;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Backup $backup, User $user)
  {
    $this->backup = $backup;
    $this->user = $user;
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
      'title'=>'Cashier\'s Module', 
      'message'=> $this->user->name.' successfully uploaded and processed '
      .$this->backup->filename.' - '.$this->backup->cashier
    ];
  }
}
