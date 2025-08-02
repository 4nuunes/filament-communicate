<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Models;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Traits\HasUserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageApproval extends Model
{
    use HasFactory;
    use HasUserModel;

    protected $fillable = [
        'message_id',
        'approver_id',
        'action',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'action' => MessageStatus::class,
        'metadata' => 'array',
    ];

    // Relacionamentos
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo($this->getUserModel(), 'approver_id');
    }

    /**
     * Verifica se a aprovação foi positiva
     */
    public function isApproved(): bool
    {
        return $this->action === MessageStatus::APPROVED;
    }

    /**
     * Verifica se a aprovação foi negativa
     */
    public function isRejected(): bool
    {
        return $this->action === MessageStatus::REJECTED;
    }

    /**
     * Verifica se está pendente
     */
    public function isPending(): bool
    {
        return $this->action === MessageStatus::PENDING;
    }
}
