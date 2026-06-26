<?php

namespace App\Providers;

use App\Contracts\CourtRepositoryInterface;
use App\Repositories\CourtRepository;
use Illuminate\Support\ServiceProvider;
use App\Strategies\Payout\PayoutStrategy;
use App\Strategies\Payout\FixedCommissionStrategy;

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
        // Đăng ký PayoutStrategy
          $this->app->bind(

            PayoutStrategy::class,

            FixedCommissionStrategy::class
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}