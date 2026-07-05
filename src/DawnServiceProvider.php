<?php

declare(strict_types=1);

namespace Dawn;

use Dawn\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DawnServiceProvider extends ServiceProvider
{
    /**
     * Register Dawn's authentication routes, mirroring Dusk's service
     * provider (environment-gated, never in production).
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            return;
        }

        Route::group(array_filter([
            'prefix' => config('dawn.path', '_dawn'),
            'domain' => config('dawn.domain'),
            'middleware' => config('dawn.middleware', 'web'),
        ]), function (): void {
            Route::get('/login/{userId}/{guard?}', [UserController::class, 'login'])->name('dawn.login');

            Route::get('/logout/{guard?}', [UserController::class, 'logout'])->name('dawn.logout');

            Route::get('/user/{guard?}', [UserController::class, 'user'])->name('dawn.user');
        });
    }
}
