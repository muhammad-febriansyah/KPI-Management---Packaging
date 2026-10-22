<?php

namespace App\Providers;

use App\Services\CurrentClientService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentClientService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        View::composer('components.layouts.app', function (ViewInstance $view): void {
            $user = auth()->user();

            $view->with(
                'availableClients',
                $user?->status === 'active'
                    ? app(CurrentClientService::class)->availableFor($user)
                    : collect(),
            );
        });
    }
}
