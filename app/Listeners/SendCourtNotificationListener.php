<?php

namespace App\Listeners;

use App\Events\CourtCreatedEvent;
use App\Services\NotificationService;
use App\Models\User;

class SendCourtNotificationListener
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function handle(CourtCreatedEvent $event)
    {
        // 🔥 Lấy danh sách admin (chỉ lấy id cho nhẹ)
        $adminIds = User::where('role', 'admin')->pluck('id');

        foreach ($adminIds as $adminId) {
            $this->service->send(
                userId: $adminId,
                title: "Sân mới cần duyệt",
                content: "Chủ sân vừa tạo sân: {$event->courtName}",
                type: "court_created", // 🔥 rõ ràng hơn
                data: [
                    'court_id' => $event->courtId,
                    'owner_id' => $event->ownerId,
                    'court_name' => $event->courtName
                ]
            );
        }
    }
}