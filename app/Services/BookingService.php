<?php

namespace App\Services;

use App\Contracts\BookingRepositoryInterface;
use App\Services\SlotLockService;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Events\BookingCreatedEvent;

class BookingService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private SlotLockService            $slotLockService
    ) {}

    /**
     * BƯỚC 1 — User chọn slot → tạm giữ Redis + tạo booking pending.
     *
     * @param int   $userId
     * @param array $slots  [['field_id', 'date', 'start_time', 'end_time', 'price'], ...]
     * @return array
     * @throws Exception
     */
    public function reserveSlots(int $userId, array $slots): array
    {
        // 1. Kiểm tra DB — slot đã paid chưa
        foreach ($slots as $slot) {
            if ($this->bookingRepository->isSlotBooked(
                $slot['field_id'], $slot['date'],
                $slot['start_time'], $slot['end_time']
            )) {
                throw new Exception(
                    "Sân #{$slot['field_id']} khung {$slot['start_time']}-{$slot['end_time']} ngày {$slot['date']} đã được đặt.",
                    409
                );
            }
        }

        // 2. Lock Redis — ngăn người khác chọn trùng slot
        $lockResult = $this->slotLockService->acquireMultipleLocks($userId, $slots);
        if (!$lockResult['success']) {
            $f = $lockResult['failed_slot'];
            throw new Exception(
                "Sân #{$f['field_id']} khung {$f['start_time']}-{$f['end_time']} đang có người đặt. Vui lòng thử lại.",
                409
            );
        }

        // 3. Tạo booking trong DB
        try {
            $booking = DB::transaction(function () use ($userId, $slots) {
                $booking = $this->bookingRepository->create([
                    'user_id'     => $userId,
                    'total_price' => array_sum(array_column($slots, 'price')),
                    'status'      => 'pending',
                ]);

                foreach ($slots as $slot) {
                    $this->bookingRepository->createDetail([
                        'booking_id'   => $booking->id,
                        'field_id'     => $slot['field_id'],
                        'booking_date' => $slot['date'],
                        'start_time'   => $slot['start_time'],
                        'end_time'     => $slot['end_time'],
                        'price'        => $slot['price'],
                    ]);
                }

                return $booking;
            });
        } catch (\Throwable $e) {
            // DB lỗi → rollback Redis lock
            $this->slotLockService->releaseMultipleLocks($userId, $slots);
            throw new Exception('Không thể tạo booking. Vui lòng thử lại.', 500);
        }

        $bookingWithRelations = $this->bookingRepository->findById($booking->id);

        // Lấy owner_id của sân để gửi thông báo
        $firstDetail = $bookingWithRelations->details->first();
        $ownerId = null;
        if ($firstDetail && $firstDetail->field && $firstDetail->field->court) {
            $ownerId = $firstDetail->field->court->owner_id;
        }

        if ($ownerId) {
            event(new BookingCreatedEvent($ownerId, $bookingWithRelations->id, $userId));
        }

        return [
            'booking'            => $bookingWithRelations,
            'expires_in_seconds' => 600,
        ];
    }

    /**
     * BƯỚC 2 — Thanh toán thành công → xác nhận booking.
     * Thường gọi từ webhook cổng thanh toán.
     */
    public function confirmPayment(int $bookingId, string $paymentMethod): object
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new Exception('Booking không tồn tại.', 404);
        }
        if ($booking->status !== 'pending') {
            throw new Exception('Booking không ở trạng thái chờ thanh toán.', 422);
        }

        $this->bookingRepository->updateStatus($bookingId, 'paid', $paymentMethod);

        // Release Redis — slot đã paid, DB là nguồn sự thật từ đây
        $slots = $this->extractSlots($booking->details);
        $this->slotLockService->releaseMultipleLocks($booking->user_id, $slots);

        // Lấy owner_id của sân để gửi thông báo
        $firstDetail = $booking->details->first();
        $ownerId = null;
        if ($firstDetail && $firstDetail->field && $firstDetail->field->court) {
            $ownerId = $firstDetail->field->court->owner_id;
        }

        if ($ownerId) {
            event(new BookingCreatedEvent($ownerId, $bookingId, $booking->user_id));
        }

        return $this->bookingRepository->findById($bookingId);
    }

    /**
     * BƯỚC 3 — Hủy booking pending (user bấm hủy trước khi thanh toán).
     * Booking pending bị XÓA CỨNG khỏi DB, không lưu lại dữ liệu rác.
     * Chỉ áp dụng cho booking status = 'pending'.
     * Booking đã 'paid' phải dùng createRefundRequest() để yêu cầu hoàn tiền.
     */
    public function cancelBooking(int $bookingId, int $userId): bool
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new Exception('Booking không tồn tại.', 404);
        }
        if ($booking->user_id !== $userId) {
            throw new Exception('Bạn không có quyền hủy booking này.', 403);
        }
        if ($booking->status !== 'pending') {
            throw new Exception('Chỉ được hủy booking đang chờ thanh toán. Nếu đã thanh toán, vui lòng gửi yêu cầu hoàn tiền.', 422);
        }

        // Release Redis lock trước khi xóa
        $slots = $this->extractSlots($booking->details);
        $this->slotLockService->releaseMultipleLocks($userId, $slots);

        // Xóa cứng booking (cascade xóa luôn booking_details)
        \App\Models\Booking::where('id', $bookingId)->delete();

        return true;
    }

    /**
     * Xác nhận đơn đặt hàng là chính thức (nhưng chưa thanh toán).
     * Sẽ cập nhật trường is_confirmed = true để tránh bị xoá tự động sau 10 phút.
     */
    public function confirmBookingPending(int $bookingId, int $userId): bool
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new Exception('Booking không tồn tại.', 404);
        }
        if ($booking->user_id !== $userId) {
            throw new Exception('Bạn không có quyền xác nhận booking này.', 403);
        }
        if ($booking->status !== 'pending') {
            throw new Exception('Chỉ có thể xác nhận booking ở trạng thái chờ thanh toán.', 422);
        }

        // Cập nhật is_confirmed = true
        return \App\Models\Booking::where('id', $bookingId)->update(['is_confirmed' => true]) > 0;
    }

    /**
     * Lấy lịch sử đặt sân của một user.
     *
     * @param int $userId
     * @return array
     */
    public function getBookingHistory(int $userId): array
    {
        $bookings = $this->bookingRepository->getByUserId($userId);

        return $bookings->map(fn($b) => $this->formatBookingSummary($b))->values()->all();
    }

    /**
     * Lấy chi tiết một booking (chỉ chủ sử hữu mới xem được).
     *
     * @param int $bookingId
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function getBookingDetail(int $bookingId, int $userId): array
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new Exception('Booking không tồn tại.', 404);
        }
        if ($booking->user_id !== $userId) {
            throw new Exception('Bạn không có quyền xem booking này.', 403);
        }

        return $this->formatBookingDetail($booking);
    }

    /**
     * Format tóm tắt booking cho danh sách lịch sử.
     * Trạng thái DB phản ánh đúng nghiệp vụ, không cần override động:
     *   pending   → Chờ thanh toán
     *   paid      → Đã thanh toán
     *   cancelled → Đã gửi yêu cầu hoàn tiền (đang chờ admin duyệt)
     *   refunded  → Admin đã hoàn tiền thành công
     */
    private function formatBookingSummary(object $booking): array
    {
        $firstDetail = $booking->details->first();
        $status = $booking->status; // Lấy thẳng từ DB, không cần override

        return [
            'id'             => $booking->id,
            'status'         => $status,
            'total_price'    => (float) $booking->total_price,
            'payment_method' => $booking->payment_method,
            'slots_count'    => $booking->details->count(),
            'first_date'     => $booking->details->min('booking_date') instanceof \Carbon\Carbon
                                ? $booking->details->min('booking_date')->format('Y-m-d')
                                : $booking->details->min('booking_date'),
            'court_name'     => $firstDetail?->field?->court?->name ?? 'Sân Thể Thao',
            'address'        => $firstDetail?->field?->court?->address ?? '',
            'created_at'     => $booking->created_at,
            'slots'          => $booking->details->map(fn($d) => [
                'id'           => $d->id,
                'field_name'   => $d->field?->name ?? 'Sân con',
                'booking_date' => $d->booking_date instanceof \Carbon\Carbon
                                    ? $d->booking_date->format('Y-m-d')
                                    : $d->booking_date,
                'start_time'   => substr($d->start_time, 0, 5),
                'end_time'     => substr($d->end_time, 0, 5),
                'price'        => (float) $d->price,
            ])->values(),
        ];
    }

    /**
     * Format chi tiết đầy đủ của một booking.
     */
    private function formatBookingDetail(object $booking): array
    {
        $status = $booking->status; // Lấy thẳng từ DB

        return [
            'id'             => $booking->id,
            'status'         => $status,
            'total_price'    => (float) $booking->total_price,
            'payment_method' => $booking->payment_method,
            'created_at'     => $booking->created_at,
            'slots'          => $booking->details->map(fn($d) => [
                'id'           => $d->id,
                'field_id'     => $d->field_id,
                'booking_date' => $d->booking_date instanceof \Carbon\Carbon
                                    ? $d->booking_date->format('Y-m-d')
                                    : $d->booking_date,
                'start_time'   => substr($d->start_time, 0, 5),
                'end_time'     => substr($d->end_time, 0, 5),
                'price'        => (float) $d->price,
            ])->values(),
        ];
    }

    // Chuyển booking_details thành mảng slot để thao tác Redis
    private function extractSlots($details): array
    {
        return $details->map(fn($d) => [
            'field_id'   => $d->field_id,
            'date'       => $d->booking_date instanceof \Carbon\Carbon
                            ? $d->booking_date->format('Y-m-d')
                            : $d->booking_date,
            'start_time' => \Carbon\Carbon::parse($d->start_time)->format('H:i'),
            'end_time'   => \Carbon\Carbon::parse($d->end_time)->format('H:i'),
        ])->toArray();
    }

    /**
     * Tự động quét và XÓA CỨNG các booking PENDING quá 10 phút chưa thanh toán.
     * Giải phóng Redis lock, sau đó xóa cứng để DB luôn sạch sẽ.
     * Không lưu lại dữ liệu rác — booking pending hết hạn không có giá trị.
     */
    public function releaseExpiredBookings(): void
    {
        $expiredTime = \Carbon\Carbon::now()->subSeconds(600); // 10 phút trước

        $expiredBookings = \App\Models\Booking::where('status', 'pending')
            ->where('is_confirmed', false)
            ->where('created_at', '<', $expiredTime)
            ->with('details')
            ->get();

        foreach ($expiredBookings as $booking) {
            // Giải phóng Redis locks trước
            $slots = $this->extractSlots($booking->details);
            $this->slotLockService->releaseMultipleLocks($booking->user_id, $slots);

            // Xóa cứng khỏi DB (cascade xóa booking_details)
            $booking->delete();
        }
    }

    /**
     * Lấy toàn bộ slot của một field trong một ngày, kèm trạng thái.
     *
     * Status:
     *   "available" → slot trống (trắng)
     *   "locked"    → đang có người giữ chờ thanh toán (vàng)
     *   "booked"    → đã đặt thành công (đỏ)
     *
     * @param int    $fieldId
     * @param string $date     YYYY-MM-DD
     * @return array
     */
    public function getAllSlotsWithStatus(int $fieldId, string $date): array
    {
        // 0. Tự động giải phóng các slot giữ chỗ hết hạn (quá 10 phút)
        $this->releaseExpiredBookings();

        // 1. Tính thứ trong tuần (1=Monday ... 7=Sunday)
        $dayOfWeek = (int) \Carbon\Carbon::parse($date)->isoWeekday();

        // 2. Lấy toàn bộ slot từ field_prices theo day_of_week
        $allSlots = \App\Models\FieldPrice::where('field_id', $fieldId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($allSlots->isEmpty()) {
            return [];
        }

        // 3. Lấy map các slot đã PAID từ DB (1 query duy nhất)
        $bookedMap = $this->bookingRepository->getBookedSlotsMap($fieldId, $date);

        // 4. Duyệt từng slot, kiểm tra Redis + DB để gán status
        $result = [];

        foreach ($allSlots as $fp) {
            $startTime = substr($fp->start_time, 0, 5); // "08:00:00" → "08:00"
            $endTime   = substr($fp->end_time, 0, 5);
            $slotKey   = "{$startTime}-{$endTime}";

            // Ưu tiên check DB trước (booked là trạng thái cuối cùng, không đổi)
            if (isset($bookedMap[$slotKey])) {
                $info = $bookedMap[$slotKey];
                // Nếu đã thanh toán (paid) HOẶC là pending đã được xác nhận (is_confirmed == true) -> BOOKED (đỏ)
                if ($info['status'] === 'paid' || ($info['status'] === 'pending' && $info['is_confirmed'])) {
                    $status = 'booked';
                } else {
                    $status = 'locked';
                }
                $expiresInSeconds  = null;
            } else {
                // Check Redis lock
                $lockInfo = $this->slotLockService->getLockInfo($fieldId, $date, $startTime, $endTime);

                if ($lockInfo) {
                    $status           = 'locked';
                    $expiresInSeconds = $lockInfo['expires_in_seconds'];
                } else {
                    $status           = 'available';
                    $expiresInSeconds = null;
                }
            }

            $slot = [
                'field_price_id'     => $fp->id,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'price'              => (float) $fp->price,
                'status'             => $status,
                'expires_in_seconds' => $expiresInSeconds,
            ];

            $result[] = $slot;
        }

        return $result;
    }

    /**
     * Khởi tạo yêu cầu hoàn tiền cho một booking đã thanh toán.
     *
     * @param int   $bookingId
     * @param int   $userId
     * @param array $data       ['bank_name', 'bank_account_name', 'bank_account_number', 'reason']
     * @return object           RefundRequest
     * @throws \Exception
     */
    public function createRefundRequest(int $bookingId, int $userId, array $data): object
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new \Exception('Đơn đặt sân không tồn tại.', 404);
        }
        if ($booking->user_id !== $userId) {
            throw new \Exception('Bạn không có quyền yêu cầu hoàn tiền cho đơn hàng này.', 403);
        }
        if ($booking->status !== 'paid') {
            throw new \Exception('Chỉ đơn đặt sân đã thanh toán mới được phép yêu cầu hoàn tiền.', 422);
        }

        // Kiểm tra điều kiện: Huỷ sân trước giờ bắt đầu ít nhất 3 tiếng
        $earliestStart = null;
        foreach ($booking->details as $detail) {
            $slotDate = $detail->booking_date instanceof \Carbon\Carbon 
                ? $detail->booking_date->format('Y-m-d') 
                : $detail->booking_date;
            $slotStart = \Carbon\Carbon::parse($slotDate . ' ' . $detail->start_time);
            
            if ($earliestStart === null || $slotStart->lt($earliestStart)) {
                $earliestStart = $slotStart;
            }
        }

        if ($earliestStart !== null) {
            $limitTime = \Carbon\Carbon::now()->addHours(3);
            if ($earliestStart->lt($limitTime)) {
                throw new \Exception('Bạn chỉ được phép hủy đặt sân trước giờ bắt đầu ít nhất 3 tiếng.', 422);
            }
        }

        // Kiểm tra xem đã có yêu cầu hoàn tiền nào đang chờ xử lý cho đơn này chưa
        $exists = \App\Models\RefundRequest::where('booking_id', $bookingId)
            ->whereIn('status', ['pending', 'completed'])
            ->exists();
        if ($exists) {
            throw new \Exception('Đơn đặt sân này đã gửi yêu cầu hoàn tiền trước đó.', 409);
        }

        // Tạo bản ghi hoàn tiền + cập nhật booking status = 'cancelled'
        // (cancelled = đang chờ admin duyệt hoàn tiền)
        DB::transaction(function () use ($bookingId, $userId, $data, $booking, &$refundRequest) {
            $this->bookingRepository->updateStatus($bookingId, 'cancelled');

            $refundRequest = \App\Models\RefundRequest::create([
                'booking_id'          => $bookingId,
                'user_id'             => $userId,
                'bank_name'           => $data['bank_name'],
                'bank_account_name'   => $data['bank_account_name'],
                'bank_account_number' => $data['bank_account_number'],
                'refund_amount'       => $booking->total_price,
                'reason'              => $data['reason'] ?? null,
                'status'              => 'pending',
            ]);
        });

        return $refundRequest;
    }
}