<?php namespace App\Events\Update;

use App\Events\Event;
use App\Models\ApUpload as Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class ApUpload extends Event 
{
  use SerializesModels;
  public $old_model;
  public $new_model;
  public $change;


  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Model $new_model, Model $old_model, $change)
  {
    $this->old_model = $old_model;
    $this->new_model = $new_model;
    $this->change = $change;
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
