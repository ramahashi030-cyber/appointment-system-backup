<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Patient;
use App\Models\Staff;
use App\Observers\AuditObserver;
use App\Support\Kiosk\HomisGateway;
use App\Support\Kiosk\OdbcHomisGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HomisGateway::class, OdbcHomisGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Patient::observe(AuditObserver::class);
        Staff::observe(AuditObserver::class);
        Admin::observe(AuditObserver::class);
    }
}
