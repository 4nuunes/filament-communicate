<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMessageType extends EditRecord
{
    protected static string $resource = MessageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
