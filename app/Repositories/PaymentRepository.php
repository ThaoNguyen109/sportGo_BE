<?php

namespace App\Repositories;

use App\Contracts\PaymentRepositoryInterface;
use App\Models\Payment;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private Payment $payment) {}

    public function create(array $data): object
    {
        return $this->payment->create($data);
    }

    public function findByOrderId(string $orderId): ?object
    {
        return $this->payment
            ->with('booking.details')
            ->where('order_id', $orderId)
            ->first();
    }

    public function updateByOrderId(string $orderId, array $data): bool
    {
        return $this->payment
            ->where('order_id', $orderId)
            ->update($data) > 0;
    }
}