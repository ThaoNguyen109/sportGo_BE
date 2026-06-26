<?php

namespace App\Strategies\Payout;

use Illuminate\Support\Collection;
use App\DTO\PayoutResult;

class FixedCommissionStrategy
    implements PayoutStrategy
{
    private const COMMISSION_PERCENT = 5;

    public function calculate(
        Collection $bookings
    ): PayoutResult
    {
        /*
        |--------------------------------------------------------------------------
        | Tổng doanh thu
        |--------------------------------------------------------------------------
        */

        $grossAmount =
            $bookings->sum('total_price');

        /*
        |--------------------------------------------------------------------------
        | Tiền commission
        |--------------------------------------------------------------------------
        */

        $commissionAmount =
            $grossAmount *
            (
                self::COMMISSION_PERCENT
                / 100
            );

        /*
        |--------------------------------------------------------------------------
        | Owner thực nhận
        |--------------------------------------------------------------------------
        */

        $netAmount =
            $grossAmount -
            $commissionAmount;

        return new PayoutResult(

            grossAmount: round(
                $grossAmount,
                2
            ),

            commissionPercent:
                self::COMMISSION_PERCENT,

            commissionAmount: round(
                $commissionAmount,
                2
            ),

            netAmount: round(
                $netAmount,
                2
            )
        );
    }
}