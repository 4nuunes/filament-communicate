<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Services;

use Alessandronuunes\FilamentCommunicate\Enums\MessagePriority;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageService
{
    public function __construct(
        private MessageApprovalService $approvalService,
        private MessageTransferService $transferService,
        private MessageStatisticsService $statisticsService,
        private MessageDeliveryService $deliveryService,
        private MessageReplyService $replyService
    ) {
    }

    /**
     * Manipula mensagem recém-criada (para Observer)
     */
    public function handleMessageCreated(Message $message): void
    {
        if (! $message->relationLoaded('messageType')) {
            $message->load('messageType');
        }

        if (! $message->messageType) {
            Log::warning(__('filament-communicate::default.logs.message_created_without_type'), [
                'message_id' => $message->id,
            ]);

            return;
        }

        // Usar o método requiresApproval() que já verifica se é resposta
        if ($message->status === MessageStatus::SENT &&
            $message->requiresApproval() && // Este método já verifica se não é resposta
            $message->status !== MessageStatus::DRAFT) {

            $this->convertToApprovalRequired($message);
        }
    }

    /**
     * Marca mensagem como lida
     */
    public function markAsRead(Message $message, User $user): void
    {
        if ($message->recipient_id !== $user->id) {
            throw new Exception(__('filament-communicate::default.validation.only_recipient_can_mark_read'));
        }

        if ($message->read_at || $message->status !== MessageStatus::SENT) {
            return;
        }

        $message->update([
            'status' => MessageStatus::READ,
            'read_at' => now(),
        ]);

        Log::info(__('filament-communicate::default.logs.message_marked_as_read'), [
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Delega aprovação para o service especializado
     */
    public function approveMessage(Message $message, User $approver, ?string $reason = null): void
    {
        $this->approvalService->approveMessage($message, $approver, $reason);
    }

    /**
     * Delega rejeição para o service especializado
     */
    public function rejectMessage(Message $message, User $approver, string $reason): void
    {
        $this->approvalService->rejectMessage($message, $approver, $reason);
    }

    /**
     * Delega transferência para o service especializado
     */
    public function transferMessage(Message $message, User $newRecipient, User $transferredBy, ?string $reason = null): void
    {
        $this->transferService->transferMessage($message, $newRecipient, $transferredBy, $reason);
    }

    /**
     * Delega estatísticas para o service especializado
     */
    public function getUserStatistics(User $user): array
    {
        return $this->statisticsService->getUserStatistics($user);
    }

    /**
     * Delega badges para o service especializado
     */
    public function getMenuBadges(User $user): array
    {
        return $this->statisticsService->getMenuBadges($user);
    }

    // Métodos privados de apoio...
    private function validateMessageCreation(array $data, User $sender): void
    {
        if ($data['recipient_id'] == $sender->id) {
            throw new Exception(__('filament-communicate::default.validation.cannot_send_to_self'));
        }
    }

    private function determineInitialStatus(bool $isDraft, MessageType $messageType): MessageStatus
    {
        if ($isDraft) {
            return MessageStatus::DRAFT;
        }

        return $messageType->requires_approval ? MessageStatus::PENDING : MessageStatus::SENT;
    }

    private function createMessageRecord(array $data, User $sender, MessageType $messageType, MessageStatus $status): Message
    {
        return Message::create([
            'message_type_id' => $data['message_type_id'],
            'sender_id' => $sender->id,
            'recipient_id' => $data['recipient_id'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'priority' => $data['priority'] ?? MessagePriority::NORMAL,
            'status' => $status,
            'requires_approval' => $messageType->requires_approval,
            'sent_at' => ($status === MessageStatus::SENT) ? now() : null,
        ]);
    }

    private function handlePostCreation(Message $message, MessageType $messageType, bool $isDraft): void
    {
        if ($messageType->requires_approval && ! $isDraft) {
            $this->approvalService->createApprovalRecord($message, $messageType);
        }

        if (! $messageType->requires_approval && ! $isDraft) {
            $this->deliveryService->deliverMessage($message);
        }
    }

    /**
     * Converte mensagem para status de aprovação necessária
     */
    private function convertToApprovalRequired(Message $message): void
    {
        try {
            DB::beginTransaction();

            $message->update([
                'status' => MessageStatus::PENDING,
                'sent_at' => null,
            ]);

            $this->approvalService->createApprovalRecord($message, $message->messageType);

            DB::commit();

            Log::info(__('filament-communicate::default.logs.message_converted_to_approval'), [
                'message_id' => $message->id,
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Método para entrega direta de mensagem
     */
    public function deliverMessage(Message $message): void
    {
        $this->deliveryService->deliverMessage($message);
    }

    /**
     * Cria uma resposta para uma mensagem
     * Delega para o MessageReplyService
     */
    public function createReply(
        Message $originalMessage,
        User $sender,
        User $recipient,
        string $subject,
        string $content
    ): Message {
        return $this->replyService->createReply(
            $originalMessage,
            $sender,
            $recipient,
            $subject,
            $content
        );
    }

    /**
     * Obtém thread completo de uma mensagem
     */
    public function getMessageThread(Message $message): \Illuminate\Support\Collection
    {
        return $this->replyService->getMessageThread($message);
    }

    /**
     * Verifica se usuário pode responder
     */
    public function canReply(Message $message, User $user): bool
    {
        return $this->replyService->canReply($message, $user);
    }

    /**
     * Marca thread como lido
     */
    public function markThreadAsRead(Message $message, User $user): void
    {
        $this->replyService->markThreadAsRead($message, $user);
    }

    /**
     * Obtém estatísticas de replies
     */
    public function getReplyStatistics(Message $message): array
    {
        return $this->replyService->getReplyStatistics($message);
    }
}
