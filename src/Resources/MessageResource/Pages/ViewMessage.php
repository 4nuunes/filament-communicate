<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageResource\Pages;

use Alessandronuunes\FilamentCommunicate\Actions\ApproveMessageAction;
use Alessandronuunes\FilamentCommunicate\Actions\RejectMessageAction;
use Alessandronuunes\FilamentCommunicate\Actions\ReplyMessageAction;
use Alessandronuunes\FilamentCommunicate\Actions\TransferMessageAction;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Verificar se o usuário pode visualizar esta mensagem
        if (! MessagePermissions::canView(auth()->user(), $this->record)) {
            abort(403, __('filament-communicate::default.permissions.no_permission_to_view'));
        }

        // Marcar thread completo como lido automaticamente se o usuário for destinatário de alguma mensagem
        if ($this->record->recipient_id === auth()->id() &&
            $this->record->status === MessageStatus::SENT &&
            ! $this->record->read_at) {
            // ✅ CORREÇÃO: Marcar todo o thread como lido, não apenas uma mensagem
            app(MessageService::class)->markThreadAsRead($this->record, auth()->user());
            $this->record->refresh();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->subject;
    }

    protected function getHeaderActions(): array
    {
        $redirectUrl = $this->getResource()::getUrl('index');

        return [
            ApproveMessageAction::make('approve')
                ->message($this->record)
                ->redirectUrl($redirectUrl),
            RejectMessageAction::make('reject')
                ->message($this->record)
                ->redirectUrl($redirectUrl),
            TransferMessageAction::make('transfer')
                ->message($this->record)
                ->redirectUrl($redirectUrl),
            ReplyMessageAction::make('reply')
                ->message($this->record)
                ->redirectUrl($redirectUrl),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Thread completo com todos os dados
                Section::make()
                    ->hiddenLabel()
                    ->schema([
                        ViewEntry::make('thread')
                            ->label('')
                            ->view('filament-communicate::infolist.communicate-thread', [
                                'messages' => $this->record->getThreadMessages(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(false), // Sempre expandido
            ]);
    }
}
