<?php

namespace App\Strategies\Payout;

use App\DTO\PayoutResult;
use Illuminate\Support\Collection;

interface PayoutStrategy
{
    public function calculate(
        Collection $bookings
    ): PayoutResult;
}