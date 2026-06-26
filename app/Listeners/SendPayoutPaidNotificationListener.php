<?php

namespace App\Listeners;

use App\Events\PayoutPaidEvent;
use App\Services\NotificationService;

class SendPayoutPaidNotificationListener
{
    protected $service;

    public function __construct(
        NotificationService $service
    ) {
        $this->service = $service;
    }

    public function handle(PayoutPaidEvent $event)
    {
        $this->service->send(
            userId: $event->ownerId,

            title: "Đã nhận thanh toán",

            content:
                "Admin đã thanh toán payout #" .
                $event->payoutId,

            type: "payout_paid",

            data: [
                'payout_id' => $event->payoutId,
                'amount' => $event->amount
            ]
        );
    }
}