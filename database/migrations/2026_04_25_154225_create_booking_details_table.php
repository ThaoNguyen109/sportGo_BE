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
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id(); // BIGINT, PK

            $table->unsignedBigInteger('booking_id'); // FK -> bookings
            $table->unsignedBigInteger('field_id');   // FK -> fields

            $table->date('booking_date');

            $table->time('start_time');
            $table->time('end_time');

            $table->decimal('price', 10, 2);

            $table->timestamps();

            // Foreign keys
            $table->foreign('booking_id')
                  ->references('id')
                  ->on('bookings')
                  ->onDelete('cascade');

            $table->foreign('field_id')
                  ->references('id')
                  ->on('fields')
                  ->onDelete('cascade');

            // Index cực quan trọng cho query check slot
            $table->index(['field_id', 'booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_details');
    }
};
