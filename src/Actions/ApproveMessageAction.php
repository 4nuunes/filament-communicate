<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Actions;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ApproveMessageAction extends Action
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

        $this->label(__('filament-communicate::default.actions.approve.label'))
            ->modalHeading(__('filament-communicate::default.actions.approve.modal_heading'))
            ->modalDescription(__('filament-communicate::default.actions.approve.modal_description'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation();

        // Verificar se a action deve ser visível
        $this->visible(function () {
            if (! $this->message) {
                return false;
            }

            // Apenas para mensagens originais (não respostas) com status pendente
            if ($this->message->isReply() || $this->message->status !== MessageStatus::PENDING) {
                return false;
            }

            $supervisor = MessagePermissions::isSupervisor(auth()->user());

            return $supervisor && $this->message->sender_id !== auth()->id();
        });

        $this->action(function () {
            try {
                app(MessageService::class)->approveMessage($this->message, auth()->user());

                Notification::make()
                    ->title(__('filament-communicate::default.messages.success.message_approved'))
                    ->success()
                    ->send();

                if ($this->redirectUrl) {
                    return redirect($this->redirectUrl);
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title(__('filament-communicate::default.messages.error.approve_message'))
                    ->danger()
                    ->send();
            }
        });
    }
}
