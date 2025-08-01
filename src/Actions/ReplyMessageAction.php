<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Actions;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ReplyMessageAction extends Action
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

        $this->label(__('filament-communicate::default.actions.reply.label'))
            ->modalHeading(__('filament-communicate::default.actions.reply.modal_heading'))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('info');

        // Verificar se a action deve ser visível
        $this->visible(function () {
            if (! $this->message) {
                return false;
            }

            // Apenas para mensagens enviadas ou lidas
            return $this->message->status === MessageStatus::SENT ||
                   $this->message->status === MessageStatus::READ;
        });

        $this->form([
            TextInput::make('subject')
                ->label(__('filament-communicate::default.forms.reply.subject.label'))
                ->default(fn ($record) => __('filament-communicate::default.forms.reply.subject.prefix').$record->subject)
                ->required(),
            RichEditor::make('content')
                ->label(__('filament-communicate::default.forms.reply.content.label'))
                ->required()
                ->toolbarButtons([
                    'bold', 'italic', 'underline',
                    'bulletList', 'orderedList',
                    'link', 'undo', 'redo',
                ]),
        ]);

        $this->action(function (array $data) {
            try {
                $originalMessage = $this->message->getRootMessage();

                $currentUser = auth()->user();

                // Lógica correta: se eu sou o remetente original, respondo para o destinatário original
                // Se eu sou o destinatário original, respondo para o remetente original
                if ($originalMessage->sender_id === $currentUser->id) {
                    // Eu sou o remetente original, então respondo para o destinatário original
                    $recipient = $originalMessage->recipient;
                } else {
                    // Eu sou o destinatário original, então respondo para o remetente original
                    $recipient = $originalMessage->sender;
                }

                app(MessageService::class)->createReply(
                    $originalMessage,
                    $currentUser,
                    $recipient,
                    $data['subject'],
                    $data['content']
                );

                Notification::make()
                    ->title(__('filament-communicate::default.messages.success.reply_sent'))
                    ->success()
                    ->send();

                if ($this->redirectUrl) {
                    return redirect($this->redirectUrl);
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title(__('filament-communicate::default.messages.error.send_reply'))
                    ->danger()
                    ->send();
            }
        });
    }
}
