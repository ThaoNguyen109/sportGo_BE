<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_payouts', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Chủ sân nhận tiền
            |--------------------------------------------------------------------------
            */

            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Tổng doanh thu booking
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'gross_amount',
                12,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | % phí hệ thống
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'commission_percent',
                5,
                2
            )->default(5);

            /*
            |--------------------------------------------------------------------------
            | Số tiền hệ thống giữ
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'commission_amount',
                12,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Owner thực nhận
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'net_amount',
                12,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Trạng thái payout
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'pending',
                'paid'

            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Ghi chú admin
            |--------------------------------------------------------------------------
            */

            $table->text('note')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Ngày admin chuyển khoản
            |--------------------------------------------------------------------------
            */

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_payouts');
    }
};