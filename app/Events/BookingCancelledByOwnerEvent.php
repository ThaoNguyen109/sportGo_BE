<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class BookingCancelledByOwnerEvent
    implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public $bookingId;
    public $userId;
    public $ownerId;
    public $reason;

    public function __construct(
        int $bookingId,
        int $userId,
        int $ownerId,
        ?string $reason
    ) {
        $this->bookingId = $bookingId;
        $this->userId = $userId;
        $this->ownerId = $ownerId;
        $this->reason = $reason;
    }
}