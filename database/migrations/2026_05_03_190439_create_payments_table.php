<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique();       // mã đơn hàng gửi lên MoMo
            $table->string('transaction_id')->nullable(); // mã giao dịch MoMo trả về
            $table->string('request_id')->unique();      // requestId gửi lên MoMo
            $table->decimal('amount', 12, 2);            // số tiền thanh toán
            $table->string('payment_method')->default('momo');
            $table->string('status')->default('pending'); // pending | success | failed
            $table->text('raw_response')->nullable();     // lưu toàn bộ response MoMo để debug
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
