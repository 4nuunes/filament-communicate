<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\TagResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label(__('filament-communicate::default.actions.view.label')),
            Actions\DeleteAction::make()
                ->label(__('filament-communicate::default.actions.delete.label'))
                ->before(function ($record) {
                    // Verificar se a tag está sendo usada antes de deletar
                    if ($record->isInUse()) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('filament-communicate::default.notifications.tag.cannot_delete.title'))
                            ->body(__('filament-communicate::default.notifications.tag.cannot_delete.body'))
                            ->danger()
                            ->send();

                        return false; // Cancela a deleção
                    }
                }),
            Actions\RestoreAction::make()
                ->label(__('filament-communicate::default.actions.restore.label')),
            Actions\ForceDeleteAction::make()
                ->label(__('filament-communicate::default.actions.force_delete.label'))
                ->before(function ($record) {
                    // Verificar se a tag está sendo usada antes de deletar permanentemente
                    if ($record->isInUse()) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('filament-communicate::default.notifications.tag.cannot_force_delete.title'))
                            ->body(__('filament-communicate::default.notifications.tag.cannot_force_delete.body'))
                            ->danger()
                            ->send();

                        return false; // Cancela a deleção
                    }
                }),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-communicate::default.pages.tags.edit.title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('filament-communicate::default.notifications.tag.updated.title');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Garantir que o slug seja único e válido
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        return $data;
    }
}
