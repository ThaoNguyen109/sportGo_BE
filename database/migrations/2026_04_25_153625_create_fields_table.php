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
        Schema::create('fields', function (Blueprint $table) {
            $table->id(); // FIX ở đây

            $table->unsignedBigInteger('court_id');

            $table->string('name', 50);

            $table->boolean('is_active')->default(true);

            // 👉 nên dùng cái này thay vì created_at riêng lẻ
            $table->timestamps();

            $table->foreign('court_id')
                  ->references('id')
                  ->on('courts')
                  ->onDelete('cascade');

            $table->index('court_id');

            // 👉 tránh trùng tên sân trong cùng 1 cụm
            $table->unique(['court_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
