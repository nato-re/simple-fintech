<?php

namespace App\Providers;

use App\Http\Services\AuthorizationService;
use App\Http\Services\Contracts\AuthorizationServiceInterface;
use App\Http\Services\Contracts\NotificationServiceInterface;
use App\Http\Services\NotificationService;
use App\Repositories\Contracts\TransferRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);

        // Register repositories
        $this->app->bind(
            WalletRepositoryInterface::class,
            WalletRepository::class
        );

        $this->app->bind(
            TransferRepositoryInterface::class,
            TransferRepository::class
        );

        // Register external services
        $this->app->bind(
            AuthorizationServiceInterface::class,
            AuthorizationService::class
        );

        $this->app->bind(
            NotificationServiceInterface::class,
            NotificationService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
