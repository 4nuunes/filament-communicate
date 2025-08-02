<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMessageTypes extends ListRecords
{
    protected static string $resource = MessageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament-communicate::default.actions.create_message_type')),
        ];
    }
}
