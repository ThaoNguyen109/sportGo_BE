<?php

namespace App\Listeners;

use App\Events\BookingCancelledByOwnerEvent;
use App\Services\NotificationService;

class SendUserBookingCancelledListener
{
    protected $service;

    public function __construct(
        NotificationService $service
    ) {
        $this->service = $service;
    }

    public function handle(
        BookingCancelledByOwnerEvent $event
    ) {
        $this->service->send(
            userId: $event->userId,

            title: "Booking bị hủy",

            content:
                "Chủ sân đã hủy booking của bạn",

            type: "booking_cancelled_by_owner",

            data: [
                'booking_id' => $event->bookingId,
                'reason' => $event->reason
            ]
        );
    }
}