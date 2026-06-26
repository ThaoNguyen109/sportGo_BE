<?php

namespace App\DTO;

class PayoutResult
{
    public function __construct(

        public float $grossAmount,

        public float $commissionPercent,

        public float $commissionAmount,

        public float $netAmount
    ) {}
}