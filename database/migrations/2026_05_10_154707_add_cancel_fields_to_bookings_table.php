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
        Schema::table('bookings', function (Blueprint $table) {

            $table->text('cancel_reason')
                ->nullable()
                ->after('status');

            /*
            |--------------------------------------------------------------------------
            | cancelled_by
            |--------------------------------------------------------------------------
            | user = người dùng hủy
            | owner = chủ sân hủy
            |--------------------------------------------------------------------------
            */

            $table->enum('cancelled_by', [
                'user',
                'owner'
            ])->nullable()->after('cancel_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            $table->dropColumn([
                'cancel_reason',
                'cancelled_by'
            ]);
        });
    }
};