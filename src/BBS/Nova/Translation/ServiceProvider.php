<?php

namespace BBS\Nova\Translation;

use BBS\Nova\Translation\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Package ID.
     *
     * @var string
     */
    const PACKAGE_ID = 'nova-translation';

    /**
     * Bootstrap Kernel.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootPackage();

        if ($this->isNovaInstalled()) {
            $this->app->booted(function () {
                $this->bootRoutes();
            });

            $this->loadNovaTranslations();
        }
    }

    /**
     * Boot Laravel package.
     *
     * @return void
     */
    protected function bootPackage()
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/config.php', static::PACKAGE_ID);
        $this->loadMigrationsFrom(__DIR__.'/../../../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../../../resources/lang', static::PACKAGE_ID);

        $this->publishes([
            __DIR__.'/../../../../config/config.php' => base_path('config/'.static::PACKAGE_ID.'.php'),
        ]);
    }

    /**
     * Check if Laravel Nova is installed.
     *
     * @return bool
     */
    protected function isNovaInstalled()
    {
        return class_exists('Laravel\Nova\Nova');
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', Authorize::class])
            ->prefix('nova-vendor/'.static::PACKAGE_ID)
            ->group(__DIR__.'/../../../../routes/api.php');
    }

    /**
     * Load prefixed Nova translations.
     *
     * @return void
     */
    protected function loadNovaTranslations()
    {
        $file = __DIR__.'/../../../../resources/lang/'.app()->getLocale().'.json';
        if (! file_exists($file)) {
            $file = __DIR__.'/../../../../resources/lang/en.json';
        }

        $translations = json_decode(file_get_contents($file), true);
        $translations = collect($translations)->mapWithKeys(function ($value, $key) {
            return [static::PACKAGE_ID.'::'.$key => $value];
        })->toArray();

        \Laravel\Nova\Nova::translations($translations);
    }
}
