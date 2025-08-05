<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Policies;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Illuminate\Foundation\Auth\User as Authenticatable;

class MessagePolicy
{
    /**
     * Determina se o usuário pode excluir a mensagem
     */
    public function delete(?Authenticatable $user, Message $message): bool
    {
        if (! $user) {
            return false;
        }

        // Super admins podem excluir qualquer mensagem
        if (MessagePermissions::isSuperAdmin($user)) {
            return true;
        }

        // Supervisores podem excluir mensagens pendentes que não sejam suas
        if (MessagePermissions::isSupervisor($user)) {
            return $this->canSupervisorDelete($user, $message);
        }

        // Usuários comuns só podem excluir suas próprias mensagens
        return $this->canUserDelete($user, $message);
    }

    /**
     * Determina se o usuário pode forçar exclusão (hard delete)
     */
    public function forceDelete(?Authenticatable $user, Message $message): bool
    {
        if (! $user) {
            return false;
        }

        // Apenas super admins podem fazer hard delete
        return MessagePermissions::isSuperAdmin($user);
    }

    /**
     * Determina se o usuário pode restaurar mensagem excluída
     */
    public function restore(?Authenticatable $user, Message $message): bool
    {
        if (! $user) {
            return false;
        }

        // Super admins e supervisores podem restaurar
        return MessagePermissions::isSuperAdmin($user) ||
               MessagePermissions::isSupervisor($user);
    }

    /**
     * Determina se o usuário pode excluir qualquer mensagem (bulk delete)
     */
    public function deleteAny(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        // Super admins e supervisores podem fazer exclusão em massa
        return MessagePermissions::isSuperAdmin($user) ||
               MessagePermissions::isSupervisor($user);
    }

    /**
     * Determina se o usuário pode forçar exclusão de qualquer mensagem (bulk force delete)
     */
    public function forceDeleteAny(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        // Apenas super admins podem fazer hard delete em massa
        return MessagePermissions::isSuperAdmin($user);
    }

    /**
     * Determina se o usuário pode restaurar qualquer mensagem (bulk restore)
     */
    public function restoreAny(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        // Super admins e supervisores podem restaurar em massa
        return MessagePermissions::isSuperAdmin($user) ||
               MessagePermissions::isSupervisor($user);
    }

    /**
     * Regras para supervisores excluírem mensagens
     */
    private function canSupervisorDelete(Authenticatable $user, Message $message): bool
    {
        // Não pode excluir próprias mensagens usando privilégios de supervisor
        if ($message->sender_id === $user->id) {
            return $this->canUserDelete($user, $message);
        }

        // Pode excluir mensagens pendentes
        return $message->status === MessageStatus::PENDING;
    }

    /**
     * Regras para usuários comuns excluírem suas mensagens
     */
    private function canUserDelete(Authenticatable $user, Message $message): bool
    {
        // Só pode excluir próprias mensagens
        if ($message->sender_id !== $user->id) {
            return false;
        }

        // Regras baseadas no status
        switch ($message->status) {
            case MessageStatus::DRAFT:
                // Rascunhos podem sempre ser excluídos
                return true;

            case MessageStatus::PENDING:
                // Mensagens pendentes podem ser excluídas
                return true;

            case MessageStatus::SENT:
            case MessageStatus::READ:
                // Mensagens enviadas/lidas só podem ser excluídas se não tiverem respostas
                return $message->replies()->count() === 0;

            case MessageStatus::APPROVED:
            case MessageStatus::REJECTED:
                // Mensagens aprovadas/rejeitadas não podem ser excluídas (preservar histórico)
                return false;

            default:
                return false;
        }
    }
}
