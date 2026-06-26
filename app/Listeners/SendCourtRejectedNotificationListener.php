<?php
namespace App\Listeners;

use App\Events\CourtRejectedEvent;
use App\Services\NotificationService;

class SendCourtRejectedNotificationListener
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function handle(CourtRejectedEvent $event)
    {
        $this->service->send(
            userId: $event->ownerId,
            title: "Sân bị từ chối",
            content: "Sân \"{$event->courtName}\" đã bị từ chối. Lý do: {$event->reason}",
            type: "court_rejected",
            data: [
                'court_id' => $event->courtId,
                'court_name' => $event->courtName,
                'reason' => $event->reason
            ]
        );
    }
}