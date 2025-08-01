<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Actions;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class TransferMessageAction extends Action
{
    protected $message = null;

    protected $redirectUrl = null;

    public function message($message): static
    {
        $this->message = $message;

        return $this;
    }

    public function redirectUrl(string $url): static
    {
        $this->redirectUrl = $url;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-communicate::default.actions.transfer.label'))
            ->modalHeading(__('filament-communicate::default.actions.transfer.modal_heading'))
            ->icon('heroicon-o-arrow-right-circle')
            ->color('info')
            ->requiresConfirmation();

        // Verificar se a action deve ser visível
        $this->visible(function () {
            if (! $this->message) {
                return false;
            }

            // Apenas para mensagens originais enviadas/lidas do destinatário atual
            return ! $this->message->replies > 0 &&
                   ($this->message->status === MessageStatus::SENT || $this->message->status === MessageStatus::READ) && // Corrigido: READ em maiúsculo
                   $this->message->current_recipient_id === auth()->id();
        });

        $this->form([
            Select::make('new_recipient_id')
                ->label(__('filament-communicate::default.forms.transfer.new_recipient_id.label'))
                ->options(function () {
                    return User::where('id', '!=', auth()->id())
                              ->where('id', '!=', $this->message->sender_id)
                              ->pluck('name', 'id');
                })
                ->required()
                ->searchable(),
            Textarea::make('reason')
                ->label(__('filament-communicate::default.forms.transfer.reason.label'))
                ->rows(3),
        ]);

        $this->action(function (array $data) {
            try {
                $newRecipient = User::find($data['new_recipient_id']);

                app(MessageService::class)->transferMessage(
                    $this->message,
                    $newRecipient,
                    auth()->user(),
                    $data['reason'] ?? null
                );

                Notification::make()
                    ->title(__('filament-communicate::default.messages.success.message_transferred'))
                    ->success()
                    ->send();

                if ($this->redirectUrl) {
                    return redirect($this->redirectUrl);
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title(__('filament-communicate::default.messages.error.transfer_message'))
                    ->danger()
                    ->send();
            }
        });
    }
}
