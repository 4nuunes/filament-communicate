<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\TagResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament-communicate::default.actions.create_tag')),
        ];
    }
}
