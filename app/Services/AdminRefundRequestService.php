<?php

namespace App\Services;

use App\Models\RefundRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminRefundRequestService
{
    /**
     * Lấy thông tin hoàn tiền theo booking
     */
    public function getRefundByBooking(
        int $bookingId
    )
    {
        $refund = RefundRequest::query()

            ->with([

                'user:id,name,email,phone',

                'booking.details.field.court'

            ])

            ->where(
                'booking_id',
                $bookingId
            )

            ->first();

        if (!$refund) {

            throw new HttpException(
                404,
                'Booking chưa có yêu cầu hoàn tiền'
            );
        }

        return $refund;
    }
}