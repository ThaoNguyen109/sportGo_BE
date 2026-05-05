<?php

namespace App\Providers;

use App\Contracts\CourtRepositoryInterface;
use App\Repositories\CourtRepository;
use Illuminate\Support\ServiceProvider;
use App\Contracts\BookingRepositoryInterface;
use App\Repositories\BookingRepository;
use App\Contracts\PaymentRepositoryInterface;
use App\Repositories\PaymentRepository;

/**
 * AppServiceProvider
 * 
 * Pattern: Service Provider (Laravel specific)
 * Reason: Central place to register bindings in the Service Container
 * 
 * SOLID: Dependency Inversion Principle (D)
 * Here we bind interfaces to concrete implementations
 * Benefit:
 * - Easy to swap implementations
 * - When Service/Controller requests CourtRepositoryInterface,
 *   Laravel automatically provides CourtRepository instance
 * - Can add decorator or caching layer without changing Service code
 * 
 * Example workflow:
 * 1. Controller needs CourtService
 * 2. CourtService needs CourtRepositoryInterface
 * 3. Container looks up: CourtRepositoryInterface -> CourtRepository
 * 4. Injects CourtRepository instance into CourtService
 * 5. Injects CourtService into Controller
 * 
 * This is called DEPENDENCY INJECTION (DI)
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * 
     * This is where we bind interfaces to implementations
     * Called when application starts
     */
    public function register(): void
    {
        /**
         * Bind CourtRepositoryInterface to CourtRepository
         * 
         * SOLID: Dependency Inversion
         * Whenever a class requests CourtRepositoryInterface,
         * Laravel will provide an instance of CourtRepository
         * 
         * $this->app->bind(interface, concrete_class)
         * 
         * Benefit: If we want to add caching, create CachedCourtRepository:
         *   $this->app->bind(CourtRepositoryInterface, CachedCourtRepository::class);
         *   No need to change CourtService or CourtController!
         */
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
     * Bootstrap any application services.
     * 
     * Called after all services are registered
     * Use for boot-time logic
     */
    public function boot(): void
    {
        //
    }
}
