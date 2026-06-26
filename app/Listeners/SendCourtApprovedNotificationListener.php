<?php

namespace App\Listeners;

use App\Events\CourtApprovedEvent;
use App\Services\NotificationService;

class SendCourtApprovedNotificationListener
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function handle(CourtApprovedEvent $event)
    {
        $this->service->send(
            userId: $event->ownerId,
            title: "Sân đã được duyệt",
            content: "Sân \"{$event->courtName}\" của bạn đã được admin phê duyệt",
            type: "court_approved",
            data: [
                'court_id' => $event->courtId,
                'court_name' => $event->courtName
            ]
        );
    }
}