<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Helpers;

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Notifications\MessageNotification;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class MessageNotificationHelper
{
    /**
     * Envia notificação para supervisor sobre mensagem pendente
     */
    public static function notifyPendingApproval(User $supervisor, Message $message): void
    {
        Notification::make()
            ->title(__('filament-communicate::default.notifications.pending_approval.title'))
            ->body(__('filament-communicate::default.notifications.pending_approval.body', [
                'sender_name' => $message->sender->name,
                'subject' => $message->subject,
            ]))
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->duration(8000)
            ->actions([
                Action::make('view')
                    ->label(__('filament-communicate::default.notifications.actions.view_message'))
                    ->url('/messages/'.$message->id)
                    ->button()
                    ->markAsRead() // Marca como lida automaticamente
                    ->close(),
            ])
            ->sendToDatabase($supervisor);
    }

    /**
     * Notifica remetente sobre aprovação
     */
    public static function notifyApproved(User $sender, Message $message): void
    {
        Notification::make()
            ->title(__('filament-communicate::default.notifications.approved.title'))
            ->body(__('filament-communicate::default.notifications.approved.body', [
                'subject' => $message->subject,
            ]))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->duration(4000)
            ->actions([
                Action::make('view')
                    ->label(__('filament-communicate::default.notifications.actions.view_message'))
                    ->url('/messages/'.$message->id)
                    ->button()
                    ->markAsRead() // Marca como lida automaticamente
                    ->close(), // Fecha a notificação
                Action::make('dismiss')
                    ->label(__('filament-communicate::default.notifications.actions.dismiss'))
                    ->color('gray')
                    ->close(),
            ])
            ->sendToDatabase($sender);
    }

    /**
     * Notifica destinatário sobre nova mensagem
     */
    public static function notifyNewMessage(User $recipient, Message $message): void
    {
        Notification::make()
            ->title(__('filament-communicate::default.notifications.new_message.title'))
            ->body(__('filament-communicate::default.notifications.new_message.body', [
                'sender_name' => $message->sender->name,
                'subject' => $message->subject,
            ]))
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->duration(5000)
            ->actions([
                Action::make('view')
                    ->label(__('filament-communicate::default.notifications.actions.view_message'))
                    ->url('/messages/'.$message->id)
                    ->button()
                    ->action(function () use ($message, $recipient) {
                        // Marcar mensagem como lida
                        if ($message->recipient_id === $recipient->id) {
                            $message->markAsRead();
                        }
                    })
                    ->markAsRead() // Marca a notificação como lida
                    ->close(), // Fecha a notificação
                Action::make('mark_read_only')
                    ->label(__('filament-communicate::default.notifications.actions.mark_as_read'))
                    ->action(function () use ($message, $recipient) {
                        if ($message->recipient_id === $recipient->id) {
                            $message->markAsRead();
                        }
                    })
                    ->color('gray')
                    ->close(),
            ])
            ->sendToDatabase($recipient);
    }

    /**
     * Notifica sobre rejeição
     */
    public static function notifyRejected(User $sender, Message $message, ?string $reason = null): void
    {
        $body = __('filament-communicate::default.notifications.rejected.body', [
            'subject' => $message->subject,
        ]);
        if ($reason) {
            $body .= ' '.__('filament-communicate::default.notifications.rejected.reason', [
                'reason' => $reason,
            ]);
        }

        Notification::make()
            ->title(__('filament-communicate::default.notifications.rejected.title'))
            ->body($body)
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->duration(6000)
            ->actions([
                Action::make('view')
                    ->label(__('filament-communicate::default.notifications.actions.view_message'))
                    ->url('/messages/'.$message->id)
                    ->button()
                    ->markAsRead() // Marca como lida automaticamente
                    ->close(), // Fecha a notificação
                Action::make('dismiss')
                    ->label(__('filament-communicate::default.notifications.actions.dismiss'))
                    ->color('gray')
                    ->close(),
            ])
            ->sendToDatabase($sender);
    }

    /**
     * Marca todas as notificações de um usuário como lidas
     */
    public static function markAllAsRead(User $user): void
    {
        DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Remove notificações antigas de um usuário
     */
    public static function cleanOldNotifications(User $user, int $days = 30): void
    {
        DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Notifica sobre uma resposta recebida
     */
    public static function notifyReply(User $recipient, Message $reply): void
    {
        try {
            // Log para debug
            \Illuminate\Support\Facades\Log::info('Sending reply notification', [
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->name,
                'reply_id' => $reply->id,
                'sender_name' => $reply->sender->name,
                'subject' => $reply->subject,
            ]);

            // Notificação do Filament (mais visível)
            Notification::make()
                ->title(__('filament-communicate::default.notifications.reply.title'))
                ->body(__('filament-communicate::default.notifications.reply.body', [
                    'sender_name' => $reply->sender->name,
                    'subject' => $reply->subject,
                ]))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->duration(6000)
                ->actions([
                    Action::make('view')
                        ->label(__('filament-communicate::default.notifications.actions.view_reply'))
                        ->url('/admin/messages/'.$reply->id)
                        ->button()
                        ->markAsRead()
                        ->close(),
                    Action::make('dismiss')
                        ->label(__('filament-communicate::default.notifications.actions.dismiss'))
                        ->color('gray')
                        ->close(),
                ])
                ->sendToDatabase($recipient);

            // Notificação por email/database (para histórico)
            $recipient->notify(new MessageNotification($reply, 'reply'));

            \Illuminate\Support\Facades\Log::info('Reply notification sent successfully', [
                'recipient_id' => $recipient->id,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error sending reply notification', [
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
