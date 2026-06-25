<?php

namespace App\Contracts;

interface PaymentRepositoryInterface
{
    // Tạo bản ghi payment mới
    public function create(array $data): object;

    // Tìm payment theo order_id (MoMo gửi lại khi callback)
    public function findByOrderId(string $orderId): ?object;

    // Cập nhật payment khi MoMo callback về
    public function updateByOrderId(string $orderId, array $data): bool;
}