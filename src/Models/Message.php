<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Models;

use Alessandronuunes\FilamentCommunicate\Enums\MessagePriority;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Observers\MessageObserver;
use Alessandronuunes\FilamentCommunicate\Traits\HasUserModel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;

#[ObservedBy(MessageObserver::class)]
class Message extends Model
{
    use HasFactory;
    use HasUserModel;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'parent_id',
        'message_type_id',
        'sender_id',
        'recipient_id',
        'current_recipient_id',
        'subject',
        'content',
        'attachments',
        'custom_data',
        'status',
        'priority',
        'read_at',
        'approved_at',
        'rejected_at',
        'delivered_at',
    ];

    protected $casts = [
        'custom_data' => 'array',
        'attachments' => 'array', // Novo cast
        'status' => MessageStatus::class,
        'priority' => MessagePriority::class,
        'read_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relacionamentos
    public function messageType(): BelongsTo
    {
        return $this->belongsTo(MessageType::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo($this->getUserModel(), 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo($this->getUserModel(), 'recipient_id');
    }

    public function currentRecipient(): BelongsTo
    {
        return $this->belongsTo($this->getUserModel(), 'current_recipient_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(MessageApproval::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(MessageTransfer::class);
    }

    /**
     * Verifica se a mensagem tem anexos
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * Conta o número de anexos
     */
    public function getAttachmentsCount(): int
    {
        return count($this->attachments ?? []);
    }

    public function latestApproval(): HasOne
    {
        return $this->hasOne(MessageApproval::class)->latest();
    }

    // Adicionar novos relacionamentos
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Verifica se é uma resposta
     */
    /**
     * Conta o total de respostas no thread da mensagem
     */
    public function getThreadReplyCount(): int
    {
        $rootMessage = $this->getRootMessage();

        return self::where('parent_id', $rootMessage->id)
            ->orWhere(function ($query) use ($rootMessage) {
                $query->whereHas('parent', function ($q) use ($rootMessage) {
                    $q->where('parent_id', $rootMessage->id);
                });
            })
            ->count();
    }

    /**
     * Verifica se a mensagem é uma resposta
     */
    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
    }

    /**
     * Obtém a mensagem raiz do thread
     */
    public function getRootMessage(): self
    {
        if ($this->isReply()) {
            return $this->parent->getRootMessage();
        }

        return $this;
    }

    /**
     * Obtém todas as mensagens do thread (incluindo a original)
     */
    public function getThreadMessages(): \Illuminate\Database\Eloquent\Collection
    {
        $root = $this->getRootMessage();

        // Buscar todas as mensagens do thread de uma vez com eager loading
        $allMessages = self::where(function ($query) use ($root) {
            $query->where('id', $root->id)
                ->orWhere('parent_id', $root->id);
        })
            ->with([
                'sender',
                'recipient',
                'latestApproval.approver',
                'transfers.fromUser',
                'transfers.toUser',
                'transfers.transferredBy',
            ])
            ->orderBy('created_at')
            ->get();

        return $allMessages;
    }

    /**
     * Verifica se a mensagem requer aprovação
     * Respostas não requerem aprovação
     */
    public function requiresApproval(): bool
    {
        if ($this->isReply()) {
            return false;
        }

        return $this->messageType->requires_approval;
    }

    /**
     * Scope para mensagens não lidas do usuário
     */
    public function scopeUnreadForUser($query, $user)
    {
        return $query->where('recipient_id', $user->id)
            ->where('status', MessageStatus::SENT)
            ->whereNull('read_at');
    }

    /**
     * Verifica se a mensagem foi lida
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Verifica se pode ser marcada como lida
     */
    public function canBeMarkedAsRead($user): bool
    {
        return $this->recipient_id === $user->id &&
               $this->status === MessageStatus::SENT &&
               ! $this->isRead();
    }

    /**
     * Verifica se a mensagem está aprovada
     */
    public function isApproved(): bool
    {
        return $this->status === MessageStatus::APPROVED;
    }

    /**
     * Verifica se a mensagem foi rejeitada
     */
    public function isRejected(): bool
    {
        return $this->status === MessageStatus::REJECTED;
    }

    /**
     * Marca a mensagem como lida
     */
    public function markAsRead(): void
    {
        Log::info('Marcando mensagem como lida 234', [
            'message_id' => $this->id,
        ]);
        $this->update([
            'status' => MessageStatus::READ,
            'read_at' => now(),
        ]);
    }

    /**
     * Gera o próximo código da mensagem baseado em timestamp
     */
    public static function generateCode(): string
    {
        $config = config('filament-communicate.message_code');

        $prefix = $config['prefix'];
        $separator = $config['separator'];

        // Gerar código baseado em timestamp: YYYYMMDD-HHMMSS
        $timestamp = now()->format('Ymd-His');

        // Usar formato customizado se definido
        if ($config['custom_format']) {
            return str_replace(
                ['{prefix}', '{timestamp}'],
                [$prefix, $timestamp],
                $config['custom_format']
            );
        }

        // Construir código: PREFIX-YYYYMMDD-HHMMSS
        return $prefix.$separator.$timestamp;
    }

    // Método para obter código de exibição
    public function getDisplayCode(): ?string
    {
        return $this->code;
    }

    // ✅ Atualizar o método boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($message) {
            // Só gerar código automático se não for uma resposta e não tiver código
            if (! $message->parent_id && ! $message->code) {
                $message->code = static::generateCode();
            }
            // Para respostas, o código será definido manualmente no createReply
        });
    }
}
