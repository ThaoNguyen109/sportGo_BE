<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;
use App\Services\NotificationService;

class SendPaymentSuccessNotificationListener
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function handle(PaymentSuccessEvent $event)
    {
        $this->service->send(
            userId: $event->userId,
            title: "Thanh toán thành công",
            content: "Bạn đã thanh toán thành công cho booking #" . $event->bookingId,
            type: "payment_success",
            data: [
                'booking_id' => $event->bookingId,
                'amount' => $event->amount
            ]
        );
    }
}