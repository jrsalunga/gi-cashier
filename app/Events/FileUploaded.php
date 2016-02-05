<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FileUploaded extends Event
{
    use SerializesModels;
    public $id;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(File $file)
    {
        $this->id = $file->id;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
