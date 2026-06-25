<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use App\Services\SlotLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class BookingController extends Controller
{
    public function __construct(
        private BookingService  $bookingService,
        private SlotLockService $slotLockService
    ) {}

    /**
     * GET /api/bookings
     * Lịch sử đặt sân của user đang đăng nhập.
     */
    public function index(): JsonResponse
    {
        try {
            $history = $this->bookingService->getBookingHistory(auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Lấy lịch sử đặt sân thành công',
                'total'   => count($history),
                'data'    => $history,
            ]);

        } catch (\Throwable $e) {
            \Log::error('BookingController@index error', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Lỗi máy chủ'], 500);
        }
    }

    /**
     * GET /api/bookings/{id}
     * Xem chi tiết một booking (chỉ chủ booking mới xem được).
     */
    public function show(int $id): JsonResponse
    {
        try {
            $detail = $this->bookingService->getBookingDetail($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin booking thành công',
                'data'    => $detail,
            ]);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    /**
     * POST /api/bookings/reserve
     * User chọn slot → tạm giữ 10 phút
     */
    public function reserve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slots'              => 'required|array|min:1',
            'slots.*.field_id'   => 'required|integer|exists:fields,id',
            'slots.*.date'       => 'required|date|after_or_equal:today',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time'   => 'required|date_format:H:i',
            'slots.*.price'      => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->bookingService->reserveSlots(
                auth()->id(),
                $validated['slots']
            );

            return response()->json([
                'success' => true,
                'message' => 'Đặt slot thành công. Vui lòng thanh toán trong 10 phút.',
                'data'    => [
                    'booking_id'         => $result['booking']->id,
                    'total_price'        => $result['booking']->total_price,
                    'status'             => $result['booking']->status,
                    'expires_in_seconds' => $result['expires_in_seconds'],
                ],
            ], 201);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    /**
     * DELETE /api/bookings/{id}
     * User hủy booking
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $this->bookingService->cancelBooking($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy booking thành công.',
            ]);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    /**
     * GET /api/slots/status
     *
     * Check trạng thái của một hoặc nhiều slot cùng lúc.
     *
     * Cách dùng 1 — 1 slot (query string):
     *   GET /api/slots/status?field_id=24&date=2026-05-11&start_time=09:00&end_time=10:00
     *
     * Cách dùng 2 — nhiều slot (query string array):
     *   GET /api/slots/status?slots[0][field_id]=24&slots[0][date]=2026-05-11
     *                        &slots[0][start_time]=09:00&slots[0][end_time]=10:00
     *                        &slots[1][field_id]=24&slots[1][date]=2026-05-11
     *                        &slots[1][start_time]=10:00&slots[1][end_time]=11:00
     *
     * Response: mảng kết quả từng slot với status: available / locked / booked
     * all_available: true chỉ khi TẤT CẢ slot đều available
     */
    public function checkSlotStatus(Request $request): JsonResponse
    {
        // Hỗ trợ 2 kiểu gọi: 1 slot (field_id, date, ...) hoặc nhiều slot (slots[]=...)
        if ($request->has('slots')) {
            // --- Multi-slot mode ---
            $validated = $request->validate([
                'slots'              => 'required|array|min:1|max:10',
                'slots.*.field_id'   => 'required|integer|exists:fields,id',
                'slots.*.date'       => 'required|date',
                'slots.*.start_time' => 'required|date_format:H:i',
                'slots.*.end_time'   => 'required|date_format:H:i',
            ]);
            $slots = $validated['slots'];
        } else {
            // --- Single-slot mode (backward compatible) ---
            $validated = $request->validate([
                'field_id'   => 'required|integer|exists:fields,id',
                'date'       => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time'   => 'required|date_format:H:i',
            ]);
            $slots = [[
                'field_id'   => $validated['field_id'],
                'date'       => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time'   => $validated['end_time'],
            ]];
        }

        // Lấy tất cả booking_details đã paid để check trong 1 query duy nhất
        // Dùng TIME_FORMAT để cắt sẵn về HH:MM, tránh mismatch với request string (09:00 vs 09:00:00)
        $bookedSlots = \App\Models\BookingDetail::query()
            ->join('bookings', 'bookings.id', '=', 'booking_details.booking_id')
            ->where('bookings.status', 'paid')
            ->selectRaw(
                'booking_details.field_id,
                 DATE_FORMAT(booking_details.booking_date, \'%Y-%m-%d\') as booking_date,
                 TIME_FORMAT(booking_details.start_time, \'%H:%i\') as start_time,
                 TIME_FORMAT(booking_details.end_time,   \'%H:%i\') as end_time'
            )
            ->get();

        $results     = [];
        $allAvailable = true;

        foreach ($slots as $slot) {
            // 1. Check Redis (locked — đang có người giữ chờ thanh toán)
            $lockInfo = $this->slotLockService->getLockInfo(
                (int) $slot['field_id'],
                $slot['date'],
                $slot['start_time'],
                $slot['end_time']
            );

            if ($lockInfo) {
                $results[]   = [
                    'field_id'           => (int) $slot['field_id'],
                    'date'               => $slot['date'],
                    'start_time'         => $slot['start_time'],
                    'end_time'           => $slot['end_time'],
                    'available'          => false,
                    'status'             => 'locked',
                    'expires_in_seconds' => $lockInfo['expires_in_seconds'],
                ];
                $allAvailable = false;
                continue;
            }

            // 2. Check DB (booked — đã paid)
            // Tất cả giá trị đã được chuẩn hóa về HH:MM và Y-m-d từ selectRaw ở trên
            $isBooked = $bookedSlots->contains(function ($b) use ($slot) {
                return (int) $b->field_id  === (int) $slot['field_id']
                    && $b->booking_date    === $slot['date']
                    && $b->start_time      <  $slot['end_time']
                    && $b->end_time        >  $slot['start_time'];
            });

            $results[] = [
                'field_id'           => (int) $slot['field_id'],
                'date'               => $slot['date'],
                'start_time'         => $slot['start_time'],
                'end_time'           => $slot['end_time'],
                'available'          => !$isBooked,
                'status'             => $isBooked ? 'booked' : 'available',
                'expires_in_seconds' => null,
            ];

            if ($isBooked) {
                $allAvailable = false;
            }
        }

        return response()->json([
            'all_available' => $allAvailable,   // true chỉ khi TẤT CẢ slot đều trống
            'slots'         => $results,
        ]);
    }

    /**
     * POST /api/bookings/{id}/refund
     * Khách hàng gửi yêu cầu hoàn tiền cho booking đã thanh toán.
     */
    public function requestRefund(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'bank_name'           => 'required|string|max:255',
            'bank_account_name'   => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',
            'reason'              => 'nullable|string|max:1000',
        ]);

        try {
            $refundRequest = $this->bookingService->createRefundRequest(
                $id,
                auth()->id(),
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Gửi yêu cầu hoàn tiền thành công. Admin sẽ kiểm tra và xử lý hoàn tiền.',
                'data'    => $refundRequest,
            ], 201);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }
}