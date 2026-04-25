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
        Schema::create('court_images', function (Blueprint $table) {
            $table->id(); // BIGINT, PK

            $table->unsignedBigInteger('court_id'); // FK -> courts

            $table->string('image_url', 255);

            $table->timestamps();

            // Foreign key
            $table->foreign('court_id')
                  ->references('id')
                  ->on('courts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_images');
    }
};
