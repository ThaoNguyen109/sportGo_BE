<?php

namespace App\Providers;

use App\Contracts\CourtRepositoryInterface;
use App\Repositories\CourtRepository;
use Illuminate\Support\ServiceProvider;
use App\Contracts\BookingRepositoryInterface;
use App\Repositories\BookingRepository;
use App\Contracts\PaymentRepositoryInterface;
use App\Repositories\PaymentRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->bind(
            CourtRepositoryInterface::class,
            CourtRepository::class
        );

        $this->app->bind(
            BookingRepositoryInterface::class,
            BookingRepository::class
        );
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}