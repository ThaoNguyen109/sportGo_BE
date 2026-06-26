<?php

namespace App\Contracts;

interface BookingRepositoryInterface
{
    public function create(array $data): object;
    public function createDetail(array $data): object;
    public function findById(int $id): ?object;
    public function updateStatus(int $id, string $status, ?string $paymentMethod = null): bool;
    public function isSlotBooked(int $fieldId, string $date, string $startTime, string $endTime): bool;
    public function getBookedSlotsMap(int $fieldId, string $date): array;

    /**
     * Lấy tất cả booking của một user, sắp xếp mới nhất trước.
     */
    public function getByUserId(int $userId): object;
}