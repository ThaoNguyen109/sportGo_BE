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
        Schema::table('users', function (Blueprint $table) {

            // thêm role
            $table->enum('role', ['user', 'owner', 'admin'])
                  ->default('user')
                  ->after('password');

            // thêm status
            $table->boolean('status')
                  ->default(true)
                  ->after('role');

            // thêm phone
            $table->string('phone')
                  ->nullable()
                  ->unique()
                  ->after('email');

            // thêm avatar
            $table->string('avatar')
                  ->nullable()
                  ->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'phone', 'avatar']);
        });
    }
};
