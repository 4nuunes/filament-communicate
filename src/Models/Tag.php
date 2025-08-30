<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Models;

use Alessandronuunes\FilamentCommunicate\Enums\TagRating;
use Alessandronuunes\FilamentCommunicate\Observers\TagObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy([TagObserver::class])]
class Tag extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Campos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'rating',
        'is_active',
        'sort_order',
    ];

    /**
     * Campos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'rating' => TagRating::class,
        'sort_order' => 'integer',
    ];

    /**
     * Eventos do modelo.
     */
    protected static function boot()
    {
        parent::boot();

        // Gera slug automaticamente se não fornecido
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        // Atualiza slug se o nome for alterado
        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->getOriginal('slug'))) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Relacionamento many-to-many com mensagens.
     */
    public function messages(): BelongsToMany
    {
        return $this->belongsToMany(
            Message::class,
            'message_tags',
            'tag_id',
            'message_id'
        )->withTimestamps();
    }

    /**
     * Scope para buscar apenas tags ativas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por rating (maior para menor).
     */
    public function scopeOrderByRating(Builder $query): Builder
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Scope para ordenar por sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope para buscar por rating mínimo.
     */
    public function scopeWhereRating(Builder $query, string $operator, int $value): Builder
    {
        return $query->where('rating', $operator, $value);
    }

    /**
     * Scope para buscar por slug.
     */
    public function scopeWhereSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope para buscar tags por termo de pesquisa.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    /**
     * Verifica se a tag está sendo usada em mensagens.
     */
    public function isInUse(): bool
    {
        return $this->messages()->exists();
    }

    /**
     * Obtém o número de mensagens que usam esta tag.
     */
    public function getMessagesCountAttribute(): int
    {
        return $this->messages()->count();
    }

    /**
     * Obtém a cor da tag formatada para CSS.
     */
    public function getColorStyleAttribute(): string
    {
        return "background-color: {$this->color}; color: white;";
    }

    /**
     * Obtém o ícone da tag com prefixo heroicon se necessário.
     */
    public function getIconNameAttribute(): string
    {
        if (Str::startsWith($this->icon, 'heroicon-')) {
            return $this->icon;
        }

        return "heroicon-o-{$this->icon}";
    }

    /**
     * Obtém a descrição da tag ou um texto padrão.
     */
    public function getDescriptionOrDefaultAttribute(): string
    {
        return $this->description ?: "Tag {$this->name}";
    }

    /**
     * Obtém o texto do rating formatado.
     */
    public function getRatingTextAttribute(): string
    {
        $levels = [
            1 => 'Muito Baixo',
            2 => 'Muito Baixo',
            3 => 'Baixo',
            4 => 'Baixo',
            5 => 'Médio',
            6 => 'Médio',
            7 => 'Alto',
            8 => 'Alto',
            9 => 'Muito Alto',
            10 => 'Crítico',
        ];

        return $levels[$this->rating] ?? 'Indefinido';
    }

    /**
     * Busca tag por slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Busca tags ativas ordenadas por rating.
     */
    public static function getActiveOrderedByRating(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->orderByRating()->get();
    }

    /**
     * Busca tags por rating mínimo.
     */
    public static function getByMinRating(int $minRating): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->whereRating('>=', $minRating)->orderByRating()->get();
    }

    /**
     * Obtém as tags mais utilizadas.
     */
    public static function getMostUsed(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->withCount('messages')
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Representação em string do modelo.
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
