<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */
    public function up(): void
    {
        Schema::create(
            'owner_bank_accounts',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Owner (1 owner = 1 account)
                |--------------------------------------------------------------------------
                */

                $table->foreignId('owner_id')

                    ->unique()

                    ->constrained('users')

                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Bank info
                |--------------------------------------------------------------------------
                */

                $table->string('bank_name');

                $table->string('bank_code')
                    ->nullable();

                $table->string('account_number');

                $table->string('account_name');

                /*
                |--------------------------------------------------------------------------
                | QR image
                |--------------------------------------------------------------------------
                */

                $table->text('qr_image')
                    ->nullable();

                $table->timestamps();
            }
        );
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'owner_bank_accounts'
        );
    }
};