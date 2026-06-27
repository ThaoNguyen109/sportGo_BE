<?php

namespace App\Listeners;

use App\Events\RefundCompletedEvent;
use App\Services\NotificationService;

class SendRefundNotificationListener
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(RefundCompletedEvent $event): void
    {
        /*
        |--------------------------------------------------------------------------
        | Thông báo cho khách đặt sân
        |--------------------------------------------------------------------------
        */

        $this->notificationService->send(
            userId: $event->userId,
            title: 'Đã hoàn tiền',
            content: "Booking #{$event->bookingId} đã được hoàn tiền thành công.",
            type: 'booking_refunded',
            data: [
                'booking_id' => $event->bookingId,
                'amount' => $event->amount,
            ]
        );


        
    }
}