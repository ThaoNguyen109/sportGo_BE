<?php
namespace App\Services;

use App\Models\Notification;
use App\Events\NewNotificationEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;

class NotificationService
{
    public function send($userId, $title, $content, $type = null, $data = [])
    {
        // 1. Lưu DB
        $notification = Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'data' => $data,
            'is_read' => 0
        ]);

        // 2. Realtime
        event(new NewNotificationEvent($notification));

        return $notification;
    }

    public function getByUser(
        int $userId
    )
    {
        return Notification::query()

            ->where('user_id', $userId)

            ->latest('id')

            ->paginate(10);
    }

    public function markAsRead(
    int $notificationId,
    int $userId
)
{
    $notification = Notification::query()

        ->where('id', $notificationId)

        ->where('user_id', $userId)

        ->first();

    if (!$notification) {

        throw new HttpException(
            404,
            'Không tìm thấy thông báo'
        );
    }

    $notification->update([
        'is_read' => true
    ]);

    return $notification;
}
}