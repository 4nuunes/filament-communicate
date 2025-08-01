<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate;

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
                'create_message_approvals_table',
                'create_message_transfers_table',
                'create_messages_table',
            ])
            ->hasViews();
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
