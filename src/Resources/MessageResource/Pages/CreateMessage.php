<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageResource\Pages;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static ?string $title = null;

    public function getTitle(): string
    {
        return __('filament-communicate::default.actions.send_message');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('Enviar Mensagem'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sender_id'] = auth()->id();
        $data['code'] = Message::generateCode();
        $data['status'] = $data['save_as_draft'] ? MessageStatus::DRAFT : MessageStatus::SENT;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
