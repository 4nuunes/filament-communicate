<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Concerns;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageApproval;
use Alessandronuunes\FilamentCommunicate\Models\MessageTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasMessageRelationships
{
    /**
     * Mensagens enviadas pelo usuário
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Mensagens recebidas pelo usuário
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    /**
     * Mensagens aprovadas pelo usuário
     */
    public function approvedMessages(): HasMany
    {
        return $this->hasMany(MessageApproval::class, 'approver_id');
    }

    /**
     * Mensagens onde o usuário é o destinatário atual
     */
    public function currentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'current_recipient_id');
    }

    /**
     * Transferências de mensagens originadas pelo usuário
     */
    public function transferredFromMessages(): HasMany
    {
        return $this->hasMany(MessageTransfer::class, 'from_user_id');
    }

    /**
     * Transferências de mensagens recebidas pelo usuário
     */
    public function transferredToMessages(): HasMany
    {
        return $this->hasMany(MessageTransfer::class, 'to_user_id');
    }

    /**
     * Transferências de mensagens executadas pelo usuário
     */
    public function performedTransfers(): HasMany
    {
        return $this->hasMany(MessageTransfer::class, 'transferred_by_id');
    }

    // Métodos auxiliares para consultas específicas

    /**
     * Mensagens não lidas do usuário
     */
    public function unreadMessages(): HasMany
    {
        return $this->receivedMessages()
            ->where('status', '!=', MessageStatus::READ)
            ->where('status', '!=', MessageStatus::ARCHIVED);
    }

    /**
     * Mensagens pendentes de aprovação para o usuário
     */
    public function pendingApprovalMessages(): Builder
    {
        return Message::query()
            ->whereHas('messageType', function (Builder $query) {
                $query->where('requires_approval', true)
                    ->whereHas('approverRole', function (Builder $roleQuery) {
                        $roleQuery->whereHas('users', function (Builder $userQuery) {
                            $userQuery->where('users.id', $this->id);
                        });
                    });
            })
            ->where('status', MessageStatus::PENDING);
    }

    /**
     * Mensagens urgentes não lidas
     */
    public function urgentUnreadMessages(): HasMany
    {
        return $this->unreadMessages()
            ->where('priority', 'urgent');
    }

    /**
     * Conta mensagens não lidas
     */
    public function getUnreadMessagesCountAttribute(): int
    {
        return $this->unreadMessages()->count();
    }

    /**
     * Conta mensagens pendentes de aprovação
     */
    public function getPendingApprovalCountAttribute(): int
    {
        return $this->pendingApprovalMessages()->count();
    }

    /**
     * Verifica se o usuário tem mensagens não lidas
     */
    public function hasUnreadMessages(): bool
    {
        return $this->unreadMessages()->exists();
    }

    /**
     * Verifica se o usuário tem mensagens urgentes não lidas
     */
    public function hasUrgentUnreadMessages(): bool
    {
        return $this->urgentUnreadMessages()->exists();
    }

    /**
     * Verifica se o usuário pode aprovar mensagens
     */
    public function canApproveMessages(): bool
    {
        return $this->pendingApprovalMessages()->exists();
    }

    /**
     * Marca todas as mensagens do usuário como lidas
     */
    public function markAllMessagesAsRead(): int
    {
        return $this->unreadMessages()->update([
            'status' => MessageStatus::READ,
            'read_at' => now(),
        ]);
    }

    /**
     * Retorna estatísticas de mensagens do usuário
     */
    public function getMessageStats(): array
    {
        return [
            'sent' => $this->sentMessages()->count(),
            'received' => $this->receivedMessages()->count(),
            'unread' => $this->getUnreadMessagesCountAttribute(),
            'pending_approval' => $this->getPendingApprovalCountAttribute(),
            'approved' => $this->approvedMessages()->count(),
            'transfers_performed' => $this->performedTransfers()->count(),
        ];
    }
}
