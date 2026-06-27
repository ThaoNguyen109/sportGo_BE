<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundCompletedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $bookingId,
        public int $userId,
        public float $amount
    ) {}
}