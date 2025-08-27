<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\TagResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\TagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    public function getTitle(): string
    {
        return __('filament-communicate::default.pages.tags.create.title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('filament-communicate::default.notifications.tag.created.title');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Garantir que o slug seja único e válido
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        return $data;
    }
}
