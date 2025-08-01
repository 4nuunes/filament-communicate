<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMessageType extends ViewRecord
{
    protected static string $resource = MessageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
