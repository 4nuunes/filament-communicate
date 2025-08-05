<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Services;

use Alessandronuunes\FilamentCommunicate\Enums\MessagePriority;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;

class MessageStatisticsService
{
    /**
     * Obtém estatísticas do usuário
     */
    public function getUserStatistics(mixed $user): array
    {
        return [
            'sent_messages' => $this->getSentMessagesStats($user),
            'received_messages' => $this->getReceivedMessagesStats($user),
            'approvals' => $this->getApprovalsStats($user),
            'transfers' => $this->getTransfersStats($user),
            'last_30_days' => $this->getLast30DaysStats($user),
            'by_priority' => $this->getPriorityStats($user),
        ];
    }

    /**
     * Obtém contadores para badges do menu
     */
    public function getMenuBadges(mixed $user): array
    {
        return [
            'unread_messages' => $user->receivedMessages()->whereNull('read_at')->count(),
            'pending_approvals' => $user->pendingApprovalMessages()->count(),
            'urgent_messages' => $user->urgentUnreadMessages()->count(),
            'transferred_messages' => $user->transferredToMessages()
                ->whereHas('message', function ($query) {
                    $query->whereNull('read_at');
                })->count(),
        ];
    }

    /**
     * Estatísticas de mensagens enviadas
     */
    private function getSentMessagesStats(mixed $user): array
    {
        return [
            'total' => $user->sentMessages()->count(),
            'drafts' => $user->sentMessages()->where('status', MessageStatus::DRAFT)->count(),
            'pending_approval' => $user->sentMessages()->where('status', MessageStatus::PENDING)->count(),
            'approved' => $user->sentMessages()->where('status', MessageStatus::SENT)->count(),
            'rejected' => $user->sentMessages()->where('status', MessageStatus::REJECTED)->count(),
        ];
    }

    /**
     * Estatísticas de mensagens recebidas
     */
    private function getReceivedMessagesStats(mixed $user): array
    {
        return [
            'total' => $user->receivedMessages()->count(),
            'unread' => $user->receivedMessages()->whereNull('read_at')->count(),
            'read' => $user->receivedMessages()->whereNotNull('read_at')->count(),
            'urgent_unread' => $user->urgentUnreadMessages()->count(),
        ];
    }

    /**
     * Estatísticas de aprovações
     */
    private function getApprovalsStats(mixed $user): array
    {
        return [
            'pending' => $user->pendingApprovalMessages()->count(),
            'total_approved' => $user->approvedMessages()->where('action', 'approved')->count(),
            'total_rejected' => $user->approvedMessages()->where('action', 'rejected')->count(),
        ];
    }

    /**
     * Estatísticas de transferências
     */
    private function getTransfersStats(mixed $user): array
    {
        return [
            'transferred_from' => $user->transferredFromMessages()->count(),
            'transferred_to' => $user->transferredToMessages()->count(),
            'performed' => $user->performedTransfers()->count(),
        ];
    }

    /**
     * Estatísticas dos últimos 30 dias
     */
    private function getLast30DaysStats(mixed $user): array
    {
        return [
            'sent' => $user->sentMessages()->where('created_at', '>=', now()->subDays(30))->count(),
            'received' => $user->receivedMessages()->where('created_at', '>=', now()->subDays(30))->count(),
            'approved' => $user->approvedMessages()
                ->where('action', 'approved')
                ->where('approved_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /**
     * Estatísticas por prioridade
     */
    private function getPriorityStats(mixed $user): array
    {
        return [
            'urgent_sent' => $user->sentMessages()->where('priority', MessagePriority::URGENT)->count(),
            'urgent_received' => $user->receivedMessages()->where('priority', MessagePriority::URGENT)->count(),
            'high_sent' => $user->sentMessages()->where('priority', MessagePriority::HIGH)->count(),
            'high_received' => $user->receivedMessages()->where('priority', MessagePriority::HIGH)->count(),
        ];
    }
}
