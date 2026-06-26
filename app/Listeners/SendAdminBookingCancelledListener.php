<?php

namespace App\Listeners;

use App\Events\BookingCancelledByOwnerEvent;
use App\Models\User;
use App\Services\NotificationService;

class SendAdminBookingCancelledListener
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

        $admins = User::where(
            'role',
            'admin'
        )->pluck('id');

        foreach ($admins as $adminId) {

            $this->service->send(
                userId: $adminId,

                title: "Owner đã hủy booking",

                content:
                    "Booking #" .
                    $event->bookingId .
                    " đã bị owner hủy",

                type: "booking_cancelled_admin",

                data: [
                    'booking_id' => $event->bookingId,
                    'owner_id' => $event->ownerId,
                    'reason' => $event->reason
                ]
            );
        }
    }
}