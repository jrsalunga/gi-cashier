<?php namespace App\Events\Delete;

use App\Events\Event;
use App\Models\ApUpload as Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class ApUpload extends Event 
{
  use SerializesModels;
  public $model;


  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(Model $model)
  {
    $this->model = $model;
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
