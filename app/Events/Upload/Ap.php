<?php namespace App\Events\Upload;

use App\Events\Event;
use App\Models\FileUpload;
use App\Models\Branch;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// to debug .env queue=database & mysql=job table
class Ap extends Event implements ShouldBroadcast
{
  use SerializesModels;
  public $fileUpload;
  public $branch;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct(FileUpload $fileUpload, Branch $branch)
  {
    $this->fileUpload = $fileUpload;
    $this->branch = $branch;
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
      'title'=> 'AP Notification', 
      'message'=> $this->branch->code.' AP '. $this->fileUpload->filename .' uploaded on Cashiers Module'
    ];
  }
}
