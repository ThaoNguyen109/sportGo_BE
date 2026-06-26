<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class PayoutPaidEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public $ownerId;
    public $payoutId;
    public $amount;

    public function __construct(
        int $ownerId,
        int $payoutId,
        float $amount
    ) {
        $this->ownerId = $ownerId;
        $this->payoutId = $payoutId;
        $this->amount = $amount;
    }
}