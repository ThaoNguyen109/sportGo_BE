<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations
     */
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {

            $table->id();

            // booking bị refund
            $table->foreignId('booking_id')
                ->constrained()
                ->onDelete('cascade');

            // user gửi yêu cầu
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // thông tin nhận tiền
            $table->string('bank_name');

            $table->string('bank_account_name');

            $table->string('bank_account_number');

            // số tiền refund
            $table->decimal(
                'refund_amount',
                10,
                2
            );

            // lý do refund
            $table->text('reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | status
            |--------------------------------------------------------------------------
            | pending
            | completed
            | rejected
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'pending',
                'completed',
                'rejected'
            ])->default('pending');

            // ghi chú admin
            $table->text('admin_note')
                ->nullable();

            // thời gian hoàn tiền
            $table->timestamp('refunded_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};