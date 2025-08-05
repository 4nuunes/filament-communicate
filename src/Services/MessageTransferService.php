<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Services;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Notifications\MessageNotification;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageTransferService
{
    /**
     * Transfere uma mensagem para outro destinatário
     */
    public function transferMessage(Message $message, User $newRecipient, User $transferredBy, ?string $reason = null): void
    {
        try {
            DB::beginTransaction();

            $this->validateTransfer($message, $newRecipient, $transferredBy);

            // Criar registro de transferência
            $this->createTransferRecord($message, $newRecipient, $transferredBy, $reason);

            // Atualizar apenas o destinatário atual (preserva o destinatário original)
            $message->update([
                'current_recipient_id' => $newRecipient->id,
                'status' => MessageStatus::SENT,
                'read_at' => null,
            ]);

            // Notificar participantes
            $this->notifyTransfer($message, $newRecipient);

            DB::commit();

            Log::info('Message transferred', [
                'message_id' => $message->id,
                'from_user_id' => $transferredBy->id,
                'to_user_id' => $newRecipient->id,
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Valida se a transferência é possível
     */
    private function validateTransfer(Message $message, User $newRecipient, User $transferredBy): void
    {
        if (! in_array($message->status, [MessageStatus::SENT, MessageStatus::READ])) {
            throw new Exception(__('filament-communicate::default.exceptions.cannot_transfer_current_status'));
        }

        // Verificar se o usuário é o destinatário atual (pode ser o original ou após transferência)
        $currentRecipientId = $message->current_recipient_id ?? $message->recipient_id;
        if ($currentRecipientId !== $transferredBy->id) {
            throw new Exception(__('filament-communicate::default.exceptions.only_current_recipient_can_transfer'));
        }

        if ($newRecipient->id === $transferredBy->id) {
            throw new Exception(__('filament-communicate::default.exceptions.cannot_transfer_to_self'));
        }
    }

    /**
     * Cria registro de transferência
     */
    private function createTransferRecord(Message $message, User $newRecipient, User $transferredBy, ?string $reason): void
    {
        \Alessandronuunes\FilamentCommunicate\Models\MessageTransfer::create([
            'message_id' => $message->id,
            'from_user_id' => $message->current_recipient_id ?? $message->recipient_id,
            'to_user_id' => $newRecipient->id,
            'transferred_by_id' => $transferredBy->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Notifica sobre a transferência
     */
    private function notifyTransfer(Message $message, User $newRecipient): void
    {
        if (method_exists($newRecipient, 'notify')) {
            $newRecipient->notify(new MessageNotification($message, 'transferred'));
        }
        if (method_exists($message->sender, 'notify')) {
            $message->sender->notify(new MessageNotification($message, 'transferred_info'));
        }
    }
}
