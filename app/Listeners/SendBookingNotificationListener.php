<?php
namespace App\Listeners;

use App\Events\BookingCreatedEvent;
use App\Services\NotificationService;
use App\Models\User;

class SendBookingNotificationListener
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function handle(BookingCreatedEvent $event)
    {
        // 🔥 1. Gửi cho OWNER
        $this->service->send(
            userId: $event->ownerId,
            title: "Có đơn đặt sân mới",
            content: "Bạn có booking mới",
            type: "booking_created",
            data: [
                'booking_id' => $event->bookingId
            ]
        );

        // 🔥 2. Gửi cho ADMIN (thu tiền)
        $adminIds = User::where('role', 'admin')->pluck('id');

        foreach ($adminIds as $adminId) {
            $this->service->send(
                userId: $adminId,
                title: "Có booking mới cần xử lý",
                content: "Có đơn đặt sân mới cần kiểm tra",
                type: "booking_created_admin",
                data: [
                    'booking_id' => $event->bookingId
                ]
            );
        }
    }
}