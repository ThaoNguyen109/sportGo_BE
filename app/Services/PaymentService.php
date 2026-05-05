<?php

namespace App\Services;

use App\Contracts\BookingRepositoryInterface;
use App\Contracts\PaymentRepositoryInterface;
use App\Services\MomoService;
use App\Services\SlotLockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private BookingRepositoryInterface $bookingRepository,
        private MomoService                $momoService,
        private SlotLockService            $slotLockService,
    ) {}

    // =========================================================
    // BƯỚC A: Tạo link thanh toán MoMo
    // =========================================================

    /**
     * Khởi tạo thanh toán MoMo cho một booking.
     *
     * @param int $bookingId
     * @param int $userId     Để verify ownership
     * @return array ['payUrl' => '...', 'orderId' => '...']
     * @throws \Exception
     */
    public function initMomoPayment(int $bookingId, int $userId): array
    {
        // 1. Lấy booking, kiểm tra tồn tại + ownership
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new \Exception('Booking không tồn tại.', 404);
        }
        if ($booking->user_id !== $userId) {
            throw new \Exception('Bạn không có quyền thanh toán booking này.', 403);
        }
        if ($booking->status !== 'pending') {
            throw new \Exception('Booking không ở trạng thái chờ thanh toán.', 422);
        }

        // 2. Tạo orderId và requestId unique
        $orderId   = $this->momoService->generateOrderId($bookingId);
        $requestId = $this->momoService->generateRequestId();
        $amount    = (int) $booking->total_price;
        $orderInfo = "Thanh toán đặt sân SportGo - Booking #{$bookingId}";

        // 3. Lưu payment record vào DB (status = pending)
        $this->paymentRepository->create([
            'booking_id'     => $bookingId,
            'order_id'       => $orderId,
            'request_id'     => $requestId,
            'amount'         => $amount,
            'payment_method' => 'momo',
            'status'         => 'pending',
        ]);

        // 4. Gọi API MoMo lấy payUrl
        $result = $this->momoService->createPayment(
            $orderId,
            $requestId,
            $amount,
            $orderInfo
        );

        return $result; // ['payUrl', 'orderId', 'requestId']
    }

    // =========================================================
    // BƯỚC B: Xử lý IPN từ MoMo (quan trọng nhất)
    // =========================================================

    /**
     * Xử lý webhook IPN từ MoMo.
     * MoMo gọi endpoint này ngay sau khi user thanh toán xong.
     *
     * @param array $data  Toàn bộ data MoMo POST về
     * @return bool
     */
    public function handleIpn(array $data): bool
    {
        // 1. Xác thực chữ ký — bắt buộc, tránh giả mạo
        if (!$this->momoService->verifySignature($data)) {
            Log::warning('MoMo IPN: Chữ ký không hợp lệ', $data);
            return false;
        }

        $orderId    = $data['orderId'];
        $resultCode = (int) $data['resultCode'];

        // 2. Tìm payment theo orderId
        $payment = $this->paymentRepository->findByOrderId($orderId);
        if (!$payment) {
            Log::error('MoMo IPN: Không tìm thấy payment', ['orderId' => $orderId]);
            return false;
        }

        // 3. Tránh xử lý trùng (idempotent)
        if ($payment->status !== 'pending') {
            Log::info('MoMo IPN: Payment đã được xử lý trước đó', ['orderId' => $orderId]);
            return true;
        }

        // 4. resultCode = 0 là thanh toán thành công
        if ($resultCode === 0) {
            $this->handlePaymentSuccess($payment, $data);
        } else {
            $this->handlePaymentFailed($payment, $data);
        }

        return true;
    }

    // =========================================================
    // BƯỚC C: Xử lý redirect (user quay về app)
    // =========================================================

    /**
     * Xử lý khi MoMo redirect user về app.
     * Chỉ dùng để hiển thị kết quả cho user, KHÔNG cập nhật DB ở đây
     * (vì IPN đã xử lý rồi, hoặc IPN sẽ đến sau).
     *
     * @param array $data
     * @return array ['status' => 'success|failed|pending', 'booking_id' => ...]
     */
    public function handleReturn(array $data): array
    {
        // Xác thực chữ ký
        if (!$this->momoService->verifySignature($data)) {
            return ['status' => 'invalid', 'message' => 'Chữ ký không hợp lệ.'];
        }

        $payment = $this->paymentRepository->findByOrderId($data['orderId']);
        if (!$payment) {
            return ['status' => 'not_found', 'message' => 'Không tìm thấy giao dịch.'];
        }

        return [
            'status'     => $payment->status,         // success | failed | pending
            'booking_id' => $payment->booking_id,
            'amount'     => $payment->amount,
            'message'    => (int) $data['resultCode'] === 0
                            ? 'Thanh toán thành công!'
                            : 'Thanh toán thất bại.',
        ];
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Xử lý khi thanh toán thành công.
     */
    private function handlePaymentSuccess(object $payment, array $data): void
    {
        DB::transaction(function () use ($payment, $data) {
            // Cập nhật payment
            $this->paymentRepository->updateByOrderId($payment->order_id, [
                'status'         => 'success',
                'transaction_id' => (string) $data['transId'],
                'raw_response'   => $data,
                'paid_at'        => now(),
            ]);

            // Cập nhật booking → paid
            $this->bookingRepository->updateStatus(
                $payment->booking_id,
                'paid',
                'momo'
            );
        });

        // Release Redis lock — slot giờ được bảo vệ bởi DB
        $this->releaseSlotLocks($payment);

        Log::info('MoMo IPN: Thanh toán thành công', [
            'booking_id' => $payment->booking_id,
            'order_id'   => $payment->order_id,
            'trans_id'   => $data['transId'],
        ]);
    }

    /**
     * Xử lý khi thanh toán thất bại / bị hủy.
     */
    private function handlePaymentFailed(object $payment, array $data): void
    {
        DB::transaction(function () use ($payment, $data) {
            $this->paymentRepository->updateByOrderId($payment->order_id, [
                'status'       => 'failed',
                'raw_response' => $data,
            ]);

            $this->bookingRepository->updateStatus(
                $payment->booking_id,
                'cancelled'
            );
        });

        // Release Redis lock — slot mở lại cho người khác
        $this->releaseSlotLocks($payment);

        Log::info('MoMo IPN: Thanh toán thất bại', [
            'booking_id'  => $payment->booking_id,
            'result_code' => $data['resultCode'],
            'message'     => $data['message'],
        ]);
    }

    /**
     * Giải phóng Redis lock cho tất cả slot trong booking.
     */
    private function releaseSlotLocks(object $payment): void
    {
        $booking = $payment->booking;
        if (!$booking || !$booking->details) return;

        $slots = $booking->details->map(fn($d) => [
            'field_id'   => $d->field_id,
            'date'       => $d->booking_date instanceof \Carbon\Carbon
                            ? $d->booking_date->format('Y-m-d')
                            : $d->booking_date,
            'start_time' => substr($d->start_time, 0, 5),
            'end_time'   => substr($d->end_time, 0, 5),
        ])->toArray();

        $this->slotLockService->releaseMultipleLocks($booking->user_id, $slots);
    }
}