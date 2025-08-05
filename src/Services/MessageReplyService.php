<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Services;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessageNotificationHelper;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageReplyService
{
    public function __construct(
        private MessageDeliveryService $deliveryService
    ) {}

    /**
     * Cria uma resposta para uma mensagem
     */
    public function createReply(
        Message $originalMessage,
        User $sender,
        User $recipient,
        string $subject,
        string $content
    ): Message {
        try {
            DB::beginTransaction();

            // Validar se pode responder
            $this->validateReply($originalMessage, $sender);

            // Obter a mensagem raiz (original)
            $rootMessage = $this->getRootMessage($originalMessage);

            // Gerar código da resposta
            $replyCode = $this->generateReplyCode($rootMessage);

            // Criar a resposta
            $reply = $this->createReplyRecord(
                $originalMessage,
                $rootMessage,
                $sender,
                $recipient,
                $subject,
                $content,
                $replyCode
            );

            // Primeiro entregar a resposta (notifica o destinatário direto)
            $this->deliveryService->deliverMessage($reply);

            // Depois notificar outros participantes do thread (exceto remetente e destinatário direto)
            $this->notifyThreadParticipants($reply, $sender);

            DB::commit();

            Log::info('Reply created successfully', [
                'reply_id' => $reply->id,
                'reply_code' => $replyCode,
                'original_message_id' => $originalMessage->id,
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
            ]);

            return $reply;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating reply', [
                'error' => $e->getMessage(),
                'original_message_id' => $originalMessage->id,
                'sender_id' => $sender->id,
            ]);

            throw $e;
        }
    }

    /**
     * Obtém todas as respostas de uma mensagem
     */
    public function getReplies(Message $message): Collection
    {
        $rootMessage = $this->getRootMessage($message);

        return Message::where('parent_id', $rootMessage->id)
            ->orWhere(function ($query) use ($rootMessage) {
                $query->whereHas('parent', function ($q) use ($rootMessage) {
                    $q->where('parent_id', $rootMessage->id);
                });
            })
            ->with(['sender', 'recipient', 'messageType'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Obtém o thread completo de uma mensagem (mensagem original + todas as respostas)
     */
    public function getMessageThread(Message $message): Collection
    {
        $rootMessage = $this->getRootMessage($message);
        $replies = $this->getReplies($rootMessage);

        // Combinar mensagem original com respostas
        return collect([$rootMessage])->merge($replies)->sortBy('created_at');
    }

    /**
     * Verifica se um usuário pode responder a uma mensagem
     */
    public function canReply(Message $message, User $user): bool
    {
        // ✅ EXCEÇÃO: Administradores podem responder a qualquer mensagem
        if (MessagePermissions::isSuperAdmin($user)) {
            // Verificar apenas se a mensagem foi entregue
            return in_array($message->status, [MessageStatus::SENT, MessageStatus::READ]);
        }

        // Só pode responder se:
        // 1. A mensagem foi entregue (status SENT ou READ)
        // 2. O usuário é o destinatário ou remetente da mensagem original
        // 3. A mensagem não está arquivada

        if (! in_array($message->status, [MessageStatus::SENT, MessageStatus::READ])) {
            return false;
        }

        $rootMessage = $this->getRootMessage($message);
        $threadParticipants = $this->getThreadParticipants($rootMessage);

        return $threadParticipants->contains('id', $user->id);
    }

    /**
     * Obtém estatísticas de replies
     */
    public function getReplyStatistics(Message $message): array
    {
        $rootMessage = $this->getRootMessage($message);
        $replies = $this->getReplies($rootMessage);

        return [
            'total_replies' => $replies->count(),
            'participants_count' => $this->getThreadParticipants($rootMessage)->count(),
            'last_reply_at' => $replies->max('created_at'),
            'unread_replies' => $replies->whereNull('read_at')->count(),
        ];
    }

    /**
     * Marca todas as respostas de um thread como lidas para um usuário
     */
    public function markThreadAsRead(Message $message, User $user): void
    {
        $rootMessage = $this->getRootMessage($message);
        $replies = $this->getReplies($rootMessage);

        // Marcar mensagem original como lida se o usuário for o destinatário
        if ($rootMessage->current_recipient_id === $user->id && ! $rootMessage->read_at) {
            Log::info('Marking original message as read', [
                'message_id' => $rootMessage->id,
                'user_id' => $user->id,
            ]);
            $rootMessage->update([
                'status' => MessageStatus::READ,
                'read_at' => now(),
            ]);
        }

        // ✅ CORREÇÃO: Só marcar respostas onde o usuário é o DESTINATÁRIO
        $replies->where('current_recipient_id', $user->id) // ← Já filtra corretamente
            ->where('status', MessageStatus::SENT) // ← Adicionar: só mensagens SENT
            ->whereNull('read_at')
            ->each(function ($reply) {
                $reply->update([
                    'status' => MessageStatus::READ,
                    'read_at' => now(),
                ]);
            });

        Log::info('Thread marked as read', [
            'root_message_id' => $rootMessage->id,
            'user_id' => $user->id,
            'replies_marked' => $replies->where('current_recipient_id', $user->id)
                ->where('status', MessageStatus::SENT)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    /**
     * Valida se pode criar uma resposta
     */
    private function validateReply(Message $originalMessage, User $sender): void
    {
        if (! $this->canReply($originalMessage, $sender)) {
            throw new Exception(__('filament-communicate::default.exceptions.cannot_reply_to_message'));
        }
    }

    /**
     * Obtém a mensagem raiz (original) do thread
     */
    private function getRootMessage(Message $message): Message
    {
        // Se a mensagem tem parent_id, buscar a raiz
        if ($message->parent_id) {
            $parent = Message::find($message->parent_id);

            return $this->getRootMessage($parent);
        }

        return $message;
    }

    /**
     * Gera código único para a resposta
     */
    private function generateReplyCode(Message $rootMessage): string
    {
        // Contar quantas respostas já existem no thread
        $replyCount = Message::where('parent_id', $rootMessage->id)
            ->orWhere(function ($query) use ($rootMessage) {
                $query->whereHas('parent', function ($q) use ($rootMessage) {
                    $q->where('parent_id', $rootMessage->id);
                });
            })
            ->count();

        return $rootMessage->code.'-R'.($replyCount + 1);
    }

    /**
     * Cria o registro da resposta
     */
    private function createReplyRecord(
        Message $originalMessage,
        Message $rootMessage,
        User $sender,
        User $recipient,
        string $subject,
        string $content,
        string $replyCode
    ): Message {
        return Message::create([
            'parent_id' => $originalMessage->id,
            'code' => $replyCode,
            'message_type_id' => $originalMessage->message_type_id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'current_recipient_id' => $recipient->id,
            'subject' => $subject,
            'content' => $content,
            'status' => MessageStatus::SENT,
            'priority' => $originalMessage->priority,
            'sent_at' => now(),
        ]);
    }

    /**
     * Obtém todos os participantes únicos do thread
     */
    private function getThreadParticipants(Message $rootMessage): Collection
    {
        $participants = collect();

        // Adicionar remetente da mensagem original
        if ($rootMessage->sender) {
            $participants->push($rootMessage->sender);
        }

        // Adicionar destinatário original
        if ($rootMessage->recipient) {
            $participants->push($rootMessage->recipient);
        }

        $currentRecipientId = $rootMessage->current_recipient_id ?? $rootMessage->recipient_id;
        if ($currentRecipientId && $currentRecipientId !== $rootMessage->recipient_id) {
            $currentRecipient = User::find($currentRecipientId);
            if ($currentRecipient) {
                $participants->push($currentRecipient);
            }
        }

        // Adicionar participantes de todas as respostas
        $replies = $this->getReplies($rootMessage);
        foreach ($replies as $reply) {
            if ($reply->sender) {
                $participants->push($reply->sender);
            }
            if ($reply->recipient) {
                $participants->push($reply->recipient);
            }

            $replyCurrentRecipientId = $reply->current_recipient_id ?? $reply->recipient_id;
            if ($replyCurrentRecipientId && $replyCurrentRecipientId !== $reply->recipient_id) {
                $replyCurrentRecipient = User::find($replyCurrentRecipientId);
                if ($replyCurrentRecipient) {
                    $participants->push($replyCurrentRecipient);
                }
            }
        }

        // Filtrar participantes únicos e remover nulos
        $uniqueParticipants = $participants->filter()->unique('id');

        Log::info('Thread participants identified', [
            'root_message_id' => $rootMessage->id,
            'total_participants' => $uniqueParticipants->count(),
            'original_recipient_id' => $rootMessage->recipient_id,
            'current_recipient_id' => $rootMessage->current_recipient_id,
            'effective_current_recipient_id' => $currentRecipientId,
            'participants' => $uniqueParticipants->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            })->toArray(),
        ]);

        return $uniqueParticipants;
    }

    /**
     * Notifica todos os participantes do thread sobre uma nova resposta
     */
    private function notifyThreadParticipants(Message $reply, User $sender): void
    {
        $rootMessage = $this->getRootMessage($reply);
        $participants = $this->getThreadParticipants($rootMessage);

        Log::info('Notifying thread participants about new reply', [
            'reply_id' => $reply->id,
            'sender_id' => $sender->id,
            'total_participants' => $participants->count(),
            'participants_ids' => $participants->pluck('id')->toArray(),
        ]);

        $participantsToNotify = $participants->filter(function ($user) use ($sender, $reply) {
            return $user->id !== $sender->id && $user->id !== $reply->recipient_id;
        });

        Log::info('Participants to be notified', [
            'participants_to_notify_count' => $participantsToNotify->count(),
            'participants_to_notify_ids' => $participantsToNotify->pluck('id')->toArray(),
        ]);

        // Notificar cada participante
        foreach ($participantsToNotify as $participant) {
            try {
                Log::info('Sending reply notification', [
                    'participant_id' => $participant->id,
                    'participant_name' => $participant->name,
                    'reply_id' => $reply->id,
                ]);

                MessageNotificationHelper::notifyReply($participant, $reply);

                Log::info('Reply notification sent successfully', [
                    'participant_id' => $participant->id,
                ]);
            } catch (Exception $e) {
                Log::error('Error sending reply notification', [
                    'participant_id' => $participant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
