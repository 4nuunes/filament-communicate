<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Enums;

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Auth\Authenticatable as User;

enum MessageStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case PENDING = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SENT = 'sent';  // Renomeado de Delivered para SENT
    case READ = 'read';
    case ARCHIVED = 'archived';

    /**
     * Retorna o label em português para exibição
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => __('filament-communicate::default.enums.status.draft'),
            self::PENDING => __('filament-communicate::default.enums.status.pending_approval'),
            self::APPROVED => __('filament-communicate::default.enums.status.approved'),
            self::REJECTED => __('filament-communicate::default.enums.status.rejected'),
            self::SENT => __('filament-communicate::default.enums.status.sent'),
            self::READ => __('filament-communicate::default.enums.status.read'),
            self::ARCHIVED => __('filament-communicate::default.enums.status.archived'),
        };
    }

    /**
     * Retorna a cor para exibição no Filament
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::SENT => 'info',
            self::READ => 'primary',
            self::ARCHIVED => 'secondary',
        };
    }

    /**
     * Retorna o label baseado no contexto do usuário
     */
    public function getLabelForUser(?User $user = null, ?Message $message = null): string
    {
        // Se não há contexto, usar label padrão
        if (! $user || ! $message) {
            return $this->getLabel();
        }

        // Para destinatários, mostrar "Nova Mensagem" quando status for SENT
        if ($message->recipient_id === $user->id && $this === self::SENT) {
            return __('filament-communicate::default.enums.status.new_message');
        }

        return $this->getLabel();
    }

    /**
     * Retorna a cor baseada no contexto do usuário
     */
    public function getColorForUser(?User $user = null, ?Message $message = null): string
    {
        // Se não há contexto, usar cor padrão
        if (! $user || ! $message) {
            return $this->getColor();
        }

        // Para destinatários, mostrar cor de destaque quando status for SENT
        if ($message->recipient_id === $user->id && $this === self::SENT) {
            return 'warning'; // Cor chamativa para forçar leitura
        }

        return $this->getColor();
    }
}
