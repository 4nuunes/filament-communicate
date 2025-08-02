<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageResource\Pages;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMessage extends EditRecord
{
    protected static string $resource = MessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Verificar se pode editar (apenas rascunhos)
        if ($this->record->status !== MessageStatus::DRAFT) {
            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }

        // Verificar se é o autor
        if ($this->record->sender_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para editar esta mensagem.');
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status'] = $data['save_as_draft'] ? MessageStatus::DRAFT : MessageStatus::SENT;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
