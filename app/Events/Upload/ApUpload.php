<?php namespace App\Events\Upload;

use App\Events\Event;
use App\Models\ApUpload as APU;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class ApUpload extends Event //implements ShouldBroadcast
{
  use SerializesModels;
  public $model;
  public $user;
  public $msg;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(APU $model, $status=true)
  {
    $this->model = $model;
    $this->user = request()->user();
    $this->status = $status;

    $this->msg = $this->status 
      ? 'AP upload success! ' .$this->model->filename
      : 'Error on uploading ApUpload.';
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
      'title'=> $this->user->name.': '. $this->depslp->cashier, 
      'message'=> $this->msg
    ];
  }
}
