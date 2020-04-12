<?php namespace App\Events\Upload;

use App\Events\Event;
use App\Models\EmploymentActivity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class ExportRequestSuccess extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $empActivity;
  public $user;
  public $data;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(EmploymentActivity $empActivity, array $data)
  {
    $this->empActivity = $empActivity;
    $this->user = request()->user();
    $this->data = $data;
  }
  /**
   * Get the channels the event should be broadcast on.
   *
   * @return array
   */
  public function broadcastOn()
  {
    return ['gi.upload'];
  }

  public function broadcastWith()
  {
    return [
      'title'=> 'ExportRequestSuccess', 
      'message'=> 'Export Request Success'
    ];
  }
}
