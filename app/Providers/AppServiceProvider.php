<?php

namespace App\Providers;

use App\Contracts\CourtRepositoryInterface;
use App\Repositories\CourtRepository;
use Illuminate\Support\ServiceProvider;
use App\Strategies\Payout\PayoutStrategy;
use App\Strategies\Payout\FixedCommissionStrategy;
use App\Contracts\BookingRepositoryInterface;
use App\Repositories\BookingRepository;
use App\Contracts\PaymentRepositoryInterface;
use App\Repositories\PaymentRepository;
use App\Contracts\PaymentGatewayInterface;
use App\Factories\MomoGatewayFactory;

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

        $this->app->bind(
            BookingRepositoryInterface::class,
            BookingRepository::class
        );
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);

        // ─── Payment Gateway (Factory Method Pattern) ─────────────────────────
        // Để chuyển sang VNPay hoặc ZaloPay, chỉ cần đổi MomoGatewayFactory
        // thành VnpayGatewayFactory hoặc ZaloPayGatewayFactory tại đây.
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return (new MomoGatewayFactory())->getGateway();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}