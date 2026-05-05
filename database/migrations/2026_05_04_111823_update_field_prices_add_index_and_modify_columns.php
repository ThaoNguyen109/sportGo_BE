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
        Schema::table('field_prices', function (Blueprint $table) {

            // ⚠️ cần doctrine/dbal để dùng change()
            $table->tinyInteger('day_of_week')
                    ->default(1)
                  ->change();

            // đảm bảo default true (nếu trước đó chưa chuẩn)
            $table->boolean('is_active')
                  ->default(true)
                  ->change();

            // thêm index để tối ưu query
            $table->index(
                ['field_id', 'day_of_week', 'is_active'],
                'idx_field_prices_lookup'
            );
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('field_prices', function (Blueprint $table) {

            // rollback index
            $table->dropIndex('idx_field_prices_lookup');

            // rollback column về trạng thái cũ
            $table->tinyInteger('day_of_week')
                  ->default(1)
                  ->comment('1=Monday, 7=Sunday')
                  ->change();

            $table->boolean('is_active')
                  ->default(true)
                  ->change();
        });
    }
};
