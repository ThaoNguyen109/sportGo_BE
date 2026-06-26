<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(
        NotificationService $service
    ) {
        $this->service = $service;
    }

    /**
     * Danh sách notification của user
     */
    public function index(Request $request)
    {
        $notifications = $this->service->getByUser(
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function markAsRead(
    Request $request,
    int $id
)
{
    $notification = $this->service
        ->markAsRead(
            $id,
            $request->user()->id
        );

    return response()->json([
        'success' => true,
        'message' => 'Đã đánh dấu đã đọc',
        'data' => $notification
    ]);
}
}