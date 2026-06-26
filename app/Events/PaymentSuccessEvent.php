<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class PaymentSuccessEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public $userId;
    public $bookingId;
    public $amount;

    public function __construct($userId, $bookingId, $amount)
    {
        $this->userId = $userId;
        $this->bookingId = $bookingId;
        $this->amount = $amount;
    }
}