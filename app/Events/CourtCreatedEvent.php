<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourtCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ownerId;
    public $courtId;
    public $courtName;
    public function __construct($ownerId, $courtId, $courtName)
    {
        $this->ownerId = $ownerId;
        $this->courtId = $courtId;
        $this->courtName = $courtName;
    }

    
}
