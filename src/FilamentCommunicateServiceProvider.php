<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate;

use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCommunicateServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-communicate';

    //
    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_message_types_table',
                'create_messages_table',
                'create_message_approvals_table',
                'create_message_transfers_table',
            ])
            ->hasViews();
    }

    public function register(): void
    {
        parent::register();

        // Registrar o service provider do blade-capture-directive
        $this->app->register(BladeCaptureDirectiveServiceProvider::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Set the locale from config
        $locale = config('filament-communicate.locale', 'en');
        app()->setLocale($locale);

        // Load views with correct namespace
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-communicate');
    }
}
