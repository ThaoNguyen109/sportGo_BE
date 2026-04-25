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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); // BIGINT, PK

            $table->unsignedBigInteger('user_id'); // FK -> users

            $table->string('title', 255);
            $table->text('content')->nullable();

            $table->string('type', 50)->nullable(); // booking, payment, system...

            $table->boolean('is_read')->default(false);

            $table->timestamp('created_at')->useCurrent();

            // Foreign key
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Index
            $table->index('user_id');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
