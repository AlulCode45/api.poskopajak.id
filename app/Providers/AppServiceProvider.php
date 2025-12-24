<?php

namespace App\Providers;

use App\Contracts\AuthServiceInterface;
use App\Contracts\ReportRepositoryInterface;
use App\Contracts\ReportServiceInterface;
use App\Repositories\ReportRepository;
use App\Services\AuthService;
use App\Services\ReportService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(ReportServiceInterface::class, ReportService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
