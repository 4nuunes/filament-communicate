<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate;

use Filament\Panel;
use Filament\Actions;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\Alignment;
use Alessandronuunes\FilamentCommunicate\Resources\TagResource;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource;
use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource;

class FilamentCommunicatePlugin implements \Filament\Contracts\Plugin
{
    public static function make(): static
    {
        return new self;
    }

    public function getId(): string
    {
        return 'filament-communicate';
    }

    //
    public function register(Panel $panel): void
    {
        if (! $panel->hasDatabaseNotifications()) {
            $panel->databaseNotifications()
                ->databaseNotificationsPolling('30s');
        }

        $panel
            ->resources([
                MessageResource::class,
                MessageTypeResource::class,
                TagResource::class,
            ]);

    }

    public function boot(Panel $panel): void {
    }
}
