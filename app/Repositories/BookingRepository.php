<?php

namespace App\Repositories;

use App\Contracts\BookingRepositoryInterface;
use App\Models\Booking;
use App\Models\BookingDetail;

class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(
        private Booking       $booking,
        private BookingDetail $bookingDetail
    ) {}

    // Tạo booking mới
    public function create(array $data): object
    {
        return $this->booking->create($data);
    }

    // Tạo booking detail (1 slot)
    public function createDetail(array $data): object
    {
        return $this->bookingDetail->create($data);
    }

    // Tìm booking theo ID, load kèm details
    public function findById(int $id): ?object
    {
        return $this->booking->with('details')->find($id);
    }

    // Cập nhật trạng thái booking
    public function updateStatus(int $id, string $status, ?string $paymentMethod = null): bool
    {
        $data = ['status' => $status];
        if ($paymentMethod) {
            $data['payment_method'] = $paymentMethod;
        }
        return $this->booking->where('id', $id)->update($data) > 0;
    }

    /**
     * Kiểm tra slot đã có booking PAID trong DB chưa.
     * Không check pending — pending do Redis quản lý.
     */
    public function isSlotBooked(
        int $fieldId,
        string $date,
        string $startTime,
        string $endTime
    ): bool {
        return $this->bookingDetail
            ->join('bookings', 'bookings.id', '=', 'booking_details.booking_id')
            ->where('booking_details.field_id', $fieldId)
            ->where('booking_details.booking_date', $date)
            ->where('booking_details.start_time', '<', $endTime)
            ->where('booking_details.end_time', '>', $startTime)
            ->where('bookings.status', 'paid')
            ->exists();
    }

    public function getBookedSlotsMap(int $fieldId, string $date): array
    {
        $rows = $this->bookingDetail
            ->join('bookings', 'bookings.id', '=', 'booking_details.booking_id')
            ->where('booking_details.field_id', $fieldId)
            ->where('booking_details.booking_date', $date)
            ->where('bookings.status', 'paid')
            ->select('booking_details.start_time', 'booking_details.end_time')
            ->get();

        // Tạo map để lookup O(1) thay vì O(n)
        // Key: "08:00-10:00"
        $map = [];
        foreach ($rows as $row) {
            $key       = substr($row->start_time, 0, 5) . '-' . substr($row->end_time, 0, 5);
            $map[$key] = true;
        }

        return $map;
    }
    
}