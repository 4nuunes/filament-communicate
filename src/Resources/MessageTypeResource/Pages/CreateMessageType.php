<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMessageType extends CreateRecord
{
    protected static string $resource = MessageTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
