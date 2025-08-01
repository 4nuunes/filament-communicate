<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Actions;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class RejectMessageAction extends Action
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

        $this->label(__('filament-communicate::default.actions.reject.label'))
            ->modalHeading(__('filament-communicate::default.actions.reject.modal_heading'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation();

        $this->visible(function () {
            if (! $this->message) {
                return false;
            }

            if ($this->message->isReply() || $this->message->status !== MessageStatus::PENDING) {
                return false;
            }

            $supervisor = MessagePermissions::isSupervisor(auth()->user());

            return $supervisor && $this->message->sender_id !== auth()->id();
        });

        $this->form([
            Textarea::make('reason')
                ->label(__('filament-communicate::default.forms.rejection.reason.label'))
                ->required()
                ->rows(3),
        ]);

        $this->action(function (array $data) {
            try {
                app(MessageService::class)->rejectMessage(
                    $this->message,
                    auth()->user(),
                    $data['reason']
                );

                Notification::make()
                    ->title(__('filament-communicate::default.messages.success.message_rejected'))
                    ->success()
                    ->send();

                if ($this->redirectUrl) {
                    return redirect($this->redirectUrl);
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title(__('filament-communicate::default.messages.error.reject_message'))
                    ->danger()
                    ->send();
            }
        });
    }
}
