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
        Schema::create('courts', function (Blueprint $table) {
             $table->id(); // BIGINT, PK, AUTO_INCREMENT

            $table->unsignedBigInteger('owner_id'); // FK -> users

            $table->string('name', 100);
            $table->string('address', 255);

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('phone', 20)->nullable();
            $table->string('image', 255)->nullable();

            $table->text('description')->nullable();

            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Foreign key
            $table->foreign('owner_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
