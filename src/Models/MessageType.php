<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Models;

use Alessandronuunes\FilamentCommunicate\Observers\MessageTypeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

#[ObservedBy(MessageTypeObserver::class)]
class MessageType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'requires_approval',
        'approver_role_id',
        'custom_fields',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'custom_fields' => 'array',
        'sort_order' => 'integer',
    ];

    // ✅ Relacionamentos confirmados
    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // Scopes úteis
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
