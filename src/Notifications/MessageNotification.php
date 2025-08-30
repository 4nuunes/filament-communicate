<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Notifications;

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Cria uma nova instância da notificação
     */
    public function __construct(
        public Message $message,
        public string $type = 'new_message'
    ) {
        // Delay será configurado quando a notificação for despachada
    }

    /**
     * Canais de entrega da notificação
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Adicionar email para mensagens urgentes ou aprovações
        if ($this->message->priority === 'urgent' ||
            in_array($this->type, ['pending_approval', 'approved', 'rejected'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Notificação para o Filament (database)
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->getNotificationData();
    }

    /**
     * Notificação por email
     */
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->getNotificationData();

        return (new MailMessage())
            ->subject($data['title'])
            ->greeting(__('filament-communicate::default.notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line($data['body'])
            ->action(__('filament-communicate::default.notifications.actions.view_message'), $data['url'])
            ->line(__('filament-communicate::default.notifications.mail.footer'));
    }

    /**
     * Notificação do Filament (para exibir no painel)
     */
    public function toFilament(object $notifiable): FilamentNotification
    {
        $data = $this->getNotificationData();

        return FilamentNotification::make()
            ->title($data['title'])
            ->body($data['body'])
            ->icon($data['icon'])
            ->color($data['color'])
            ->duration($data['duration'])
            ->actions([
                Action::make('view')
                    ->label(__('filament-communicate::default.notifications.actions.view_message'))
                    ->url($data['url'])
                    ->button(),
                Action::make('mark_read')
                    ->label(__('filament-communicate::default.notifications.actions.mark_as_read'))
                    ->action(function () {
                        $this->message->update(['read_at' => now()]);
                    })
                    ->close(),
            ]);
    }

    /**
     * Dados da notificação baseados no tipo
     */
    protected function getNotificationData(): array
    {
        $baseUrl = config('app.url').'/admin/messages/'.$this->message->id;

        return match ($this->type) {
            'new_message' => [
                'title' => __('filament-communicate::default.notifications.new_message.title'),
                'body' => __('filament-communicate::default.notifications.new_message.body', [
                    'sender_name' => $this->message->sender->name,
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-envelope',
                'color' => 'info',
                'duration' => 5000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'reply' => [
                'title' => __('filament-communicate::default.notifications.reply.title'),
                'body' => __('filament-communicate::default.notifications.reply.body', [
                    'sender_name' => $this->message->sender->name,
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'color' => 'success',
                'duration' => 5000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'pending_approval' => [
                'title' => __('filament-communicate::default.notifications.pending_approval.title'),
                'body' => __('filament-communicate::default.notifications.pending_approval.body', [
                    'sender_name' => $this->message->sender->name,
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'duration' => 8000,
                'url' => $baseUrl.'/approve',
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'approved' => [
                'title' => __('filament-communicate::default.notifications.approved.title'),
                'body' => __('filament-communicate::default.notifications.approved.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
                'duration' => 4000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'rejected' => [
                'title' => __('filament-communicate::default.notifications.rejected.title'),
                'body' => __('filament-communicate::default.notifications.rejected.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
                'duration' => 6000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'transferred' => [
                'title' => __('filament-communicate::default.notifications.transferred.title'),
                'body' => __('filament-communicate::default.notifications.transferred.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-arrow-right-circle',
                'color' => 'info',
                'duration' => 5000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'transferred_info' => [
                'title' => __('filament-communicate::default.notifications.transferred_info.title'),
                'body' => __('filament-communicate::default.notifications.transferred_info.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-information-circle',
                'color' => 'info',
                'duration' => 4000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            default => [
                'title' => __('filament-communicate::default.notifications.default.title'),
                'body' => __('filament-communicate::default.notifications.default.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-bell',
                'color' => 'gray',
                'duration' => 4000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ]
        };
    }

    /**
     * Representação em array da notificação
     */
    public function toArray($notifiable): array
    {
        $baseUrl = config('app.url').'/admin/messages/'.$this->message->id;

        return match ($this->type) {
            'new_message' => [
                'title' => __('filament-communicate::default.notifications.new_message.title'),
                'body' => __('filament-communicate::default.notifications.new_message.body', [
                    'sender_name' => $this->message->sender->name,
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-envelope',
                'color' => 'info',
                'duration' => 5000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'pending_approval' => [
                'title' => __('filament-communicate::default.notifications.pending_approval.title'),
                'body' => __('filament-communicate::default.notifications.pending_approval.body', [
                    'sender_name' => $this->message->sender->name,
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'duration' => 8000,
                'url' => $baseUrl.'/approve',
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'approved' => [
                'title' => __('filament-communicate::default.notifications.approved.title'),
                'body' => __('filament-communicate::default.notifications.approved.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
                'duration' => 4000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'rejected' => [
                'title' => __('filament-communicate::default.notifications.rejected.title'),
                'body' => __('filament-communicate::default.notifications.rejected.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
                'duration' => 6000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'transferred' => [
                'title' => __('filament-communicate::default.notifications.transferred.title'),
                'body' => __('filament-communicate::default.notifications.transferred.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-arrow-right-circle',
                'color' => 'info',
                'duration' => 5000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            'transferred_info' => [
                'title' => __('filament-communicate::default.notifications.transferred_info.title'),
                'body' => __('filament-communicate::default.notifications.transferred_info.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-information-circle',
                'color' => 'info',
                'duration' => 4000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ],

            default => [
                'title' => __('filament-communicate::default.notifications.default.title'),
                'body' => __('filament-communicate::default.notifications.default.body', [
                    'subject' => $this->message->subject,
                ]),
                'icon' => 'heroicon-o-bell',
                'color' => 'gray',
                'duration' => 4000,
                'url' => $baseUrl,
                'message_id' => $this->message->id,
                'type' => $this->type,
            ]
        };
    }
}
