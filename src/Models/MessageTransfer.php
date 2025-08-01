<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTransfer extends Model
{
    protected $fillable = [
        'message_id',
        'from_user_id',
        'to_user_id',
        'transferred_by_id',
        'reason',
    ];

    // Relacionamentos
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by_id');
    }
}
