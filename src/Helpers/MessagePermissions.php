<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Helpers;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use App\Models\User;

class MessagePermissions
{
    /**
     * Verifica se o usuário pode visualizar a mensagem
     */
    public static function canView(User $user, Message $message): bool
    {
        // Super admins podem ver tudo
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Supervisores podem ver mensagens pendentes (exceto as próprias)
        if (self::isSupervisor($user)) {
            return self::canSupervisorView($user, $message);
        }

        // Usuários comuns só podem ver suas próprias mensagens
        return self::isMessageParticipant($user, $message);
    }

    /**
     * Verifica se o usuário pode aprovar mensagens
     */
    public static function canApprove(User $user, Message $message): bool
    {
        // Super admins podem aprovar tudo
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Não pode aprovar próprias mensagens
        if ($message->sender_id === $user->id) {
            return false;
        }

        // Só pode aprovar mensagens pendentes
        if ($message->status !== MessageStatus::PENDING) {
            return false;
        }

        // Verificar se tem role específico definido no tipo de mensagem
        $messageType = $message->messageType;
        if ($messageType->approverRole) {
            return $user->hasRole($messageType->approverRole->name);
        }

        // Fallback para supervisores
        return self::isSupervisor($user);
    }

    /**
     * Verifica se é super admin
     */
    public static function isSuperAdmin(User $user): bool
    {
        $superAdminRoles = config('filament-communicate.super_admin_roles', []);

        return $user->hasAnyRole($superAdminRoles);
    }

    /**
     * Verifica se é supervisor
     */
    public static function isSupervisor(User $user): bool
    {
        $supervisorRoles = config('filament-communicate.supervisor_roles', []);

        return $user->hasAnyRole($supervisorRoles);
    }

    /**
     * Verifica se o supervisor pode ver a mensagem
     */
    private static function canSupervisorView(User $user, Message $message): bool
    {
        $config = config('filament-communicate.supervisor_visibility');

        if ($message->current_recipient_id === $user->id) {
            return true;
        }
        // Verifica se o status está permitido
        if (! in_array($message->status, $config['allowed_statuses'])) {
            return false;
        }

        // Verifica se deve excluir próprias mensagens
        if ($config['exclude_own_messages'] && $message->sender_id === $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se o usuário é participante da mensagem
     */
    private static function isMessageParticipant(User $user, Message $message): bool
    {
        return $message->sender_id === $user->id ||
               $message->recipient_id === $user->id ||
               $message->current_recipient_id === $user->id;
    }

    /**
     * Aplica filtros de query baseado nas permissões do usuário
     */
    public static function applyQueryFilters($query, User $user)
    {
        // Super admins veem tudo
        if (self::isSuperAdmin($user)) {
            return $query;
        }

        // Supervisores veem mensagens pendentes + suas próprias
        if (self::isSupervisor($user)) {
            return $query->where(function ($q) use ($user) {
                // Mensagens pendentes (exceto as próprias)
                $q->where(function ($subQ) use ($user) {
                    $subQ->where('status', MessageStatus::PENDING)
                         ->where('sender_id', '!=', $user->id);
                })
                // OU suas próprias mensagens enviadas
                ->orWhere('sender_id', $user->id)
                // OU mensagens recebidas com status específicos
                ->orWhere(function ($subQ) use ($user) {
                    $subQ->where(function ($recipientQ) use ($user) {
                        $recipientQ->where('recipient_id', $user->id)
                                  ->orWhere('current_recipient_id', $user->id);
                    })
                    ->whereIn('status', [
                        MessageStatus::READ->value,
                        MessageStatus::APPROVED->value,
                        MessageStatus::SENT->value,
                    ]);
                });
            });
        }

        // Usuários comuns veem apenas suas mensagens
        return $query->where(function ($q) use ($user) {
            // Mensagens enviadas pelo usuário (qualquer status)
            $q->where('sender_id', $user->id)
            // OU mensagens recebidas com status específicos
            ->orWhere(function ($subQ) use ($user) {
                $subQ->where(function ($recipientQ) use ($user) {
                    $recipientQ->where('recipient_id', $user->id)
                              ->orWhere('current_recipient_id', $user->id);
                })
                ->whereIn('status', [
                    MessageStatus::READ->value,
                    MessageStatus::APPROVED->value,
                    MessageStatus::SENT->value,
                ]);
            });
        });
    }
}
