<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Services;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessageNotificationHelper;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageApproval;
use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageApprovalService
{
    /**
     * Aprova uma mensagem
     */
    public function approveMessage(Message $message, User $approver, ?string $reason = null): void
    {
        try {
            DB::beginTransaction();

            if ($message->status !== MessageStatus::PENDING) {
                throw new Exception(__('filament-communicate::default.exceptions.only_pending_can_be_approved'));
            }

            if (! MessagePermissions::canApprove($approver, $message)) {
                throw new Exception(__('filament-communicate::default.exceptions.no_permission_to_approve'));
            }

            // Buscar ou criar registro de aprovação
            $approval = $this->findOrCreateApproval($message, $approver);

            // Atualizar o registro de aprovação
            $approval->update([
                'action' => MessageStatus::APPROVED,
                'reason' => $reason,
            ]);

            // Atualizar status da mensagem para SENT
            $message->update([
                'status' => MessageStatus::SENT,
                'approved_at' => now(),
            ]);

            // Entregar mensagem
            app(MessageDeliveryService::class)->deliverMessage($message);

            // Notificar remetente sobre aprovação
            MessageNotificationHelper::notifyApproved($message->sender, $message);

            DB::commit();

            Log::info('Message approved', [
                'message_id' => $message->id,
                'approver_id' => $approver->id,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error approving message', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
                'approver_id' => $approver->id,
            ]);

            throw $e;
        }
    }

    /**
     * Rejeita uma mensagem
     */
    public function rejectMessage(Message $message, User $approver, string $reason): void
    {
        try {
            DB::beginTransaction();

            if ($message->status !== MessageStatus::PENDING) {
                throw new Exception(__('filament-communicate::default.exceptions.message_not_pending_approval'));
            }

            if ($message->sender_id === $approver->id) {
                throw new Exception(__('filament-communicate::default.exceptions.cannot_reject_own_message'));
            }

            if (! MessagePermissions::canApprove($approver, $message)) {
                throw new Exception(__('filament-communicate::default.exceptions.not_authorized_to_reject'));
            }

            // Buscar ou criar registro de aprovação
            $approval = $this->findOrCreateApproval($message, $approver);

            // Atualizar o registro de aprovação
            $approval->update([
                'action' => MessageStatus::REJECTED,
                'reason' => $reason,
            ]);

            // Atualizar status da mensagem
            $message->update([
                'status' => MessageStatus::REJECTED,
                'rejected_at' => now(),
            ]);

            // Notificar remetente sobre rejeição
            MessageNotificationHelper::notifyRejected($message->sender, $message, $reason);

            DB::commit();

            Log::info('Message rejected', [
                'message_id' => $message->id,
                'approver_id' => $approver->id,
                'reason' => $reason,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting message', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
                'approver_id' => $approver->id,
            ]);

            throw $e;
        }
    }

    /**
     * Cria registro de aprovação para mensagem
     */
    public function createApprovalRecord(Message $message, MessageType $messageType): void
    {
        $approver = $this->getApproverForMessageType($messageType);

        if (! $approver) {
            throw new Exception(__('filament-communicate::default.exceptions.no_approver_found'));
        }

        // Verificar se o aprovador não é o mesmo que enviou a mensagem
        if ($approver->id === $message->sender_id) {
            $approver = $this->getAlternativeApprover($messageType, $message->sender_id);
            if (! $approver) {
                throw new Exception(__('filament-communicate::default.exceptions.no_alternative_approver_found'));
            }
        }

        MessageApproval::create([
            'message_id' => $message->id,
            'approver_id' => $approver->id,
            'action' => MessageStatus::PENDING,
        ]);

        // Notificar aprovador
        MessageNotificationHelper::notifyPendingApproval($approver, $message);
    }

    /**
     * Busca ou cria registro de aprovação
     */
    private function findOrCreateApproval(Message $message, User $approver): MessageApproval
    {
        $approval = MessageApproval::where('message_id', $message->id)
            ->where('approver_id', $approver->id)
            ->first();

        if (! $approval) {
            $approval = MessageApproval::create([
                'message_id' => $message->id,
                'approver_id' => $approver->id,
                'action' => MessageStatus::PENDING,
            ]);
        }

        return $approval;
    }

    /**
     * Busca aprovador para o tipo de mensagem
     */
    private function getApproverForMessageType(MessageType $messageType): ?User
    {
        if (! $messageType->requires_approval) {
            return null;
        }

        if ($messageType->approverRole) {
            return User::role($messageType->approverRole->name)->first();
        }

        // Fallback para supervisor
        $supervisorRoles = config('filament-communicate.supervisor_roles', ['supervisor']);
        foreach ($supervisorRoles as $role) {
            $user = User::role($role)->first();
            if ($user) {
                return $user;
            }
        }

        // Último fallback para super_admin
        return User::role('super_admin')->first();
    }

    /**
     * Busca aprovador alternativo
     */
    private function getAlternativeApprover(MessageType $messageType, int $senderId): ?User
    {
        if ($messageType->approverRole) {
            return User::role($messageType->approverRole->name)
                ->where('id', '!=', $senderId)
                ->first();
        }

        $supervisorRoles = config('filament-communicate.supervisor_roles', ['supervisor']);
        foreach ($supervisorRoles as $role) {
            $user = User::role($role)->where('id', '!=', $senderId)->first();
            if ($user) {
                return $user;
            }
        }

        return User::role('super_admin')
            ->where('id', '!=', $senderId)
            ->first();
    }
}
