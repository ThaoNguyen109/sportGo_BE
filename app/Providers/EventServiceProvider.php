<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\BookingCreatedEvent;
use App\Listeners\SendBookingNotificationListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        BookingCreatedEvent::class => [
            SendBookingNotificationListener::class,
        ],

        CourtCreatedEvent::class => [
            SendCourtNotificationListener::class,
        ],
        CourtApprovedEvent::class => [
            SendCourtNotificationListener::class,
        ],
        CourtRejectedEvent::class => [
            SendCourtNotificationListener::class,
        ],
        PaymentSuccessEvent::class => [
            SendPaymentSuccessNotificationListener::class,
        ],
        PayoutPaidEvent::class => [
            SendPayoutPaidNotificationListener::class,
        ],
        BookingCancelledByOwnerEvent::class => [
            SendUserBookingCancelledListener::class,
            SendAdminBookingCancelledListener::class
        ],
    ];
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}