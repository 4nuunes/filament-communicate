<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Services;

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TagService
{
    /**
     * Aplicar tags a uma mensagem
     */
    public function applyTagsToMessage(Message $message, array $tagIds): void
    {
        // Validar se todas as tags existem e estão ativas
        $validTags = Tag::whereIn('id', $tagIds)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (count($validTags) !== count($tagIds)) {
            throw new \InvalidArgumentException('Uma ou mais tags são inválidas ou inativas.');
        }

        $message->tags()->sync($validTags);

        // Limpar cache relacionado
        $this->clearTagCache();
    }

    /**
     * Remover tags de uma mensagem
     */
    public function removeTagsFromMessage(Message $message, array $tagIds): void
    {
        $message->tags()->detach($tagIds);

        // Limpar cache relacionado
        $this->clearTagCache();
    }

    /**
     * Sincronizar tags de uma mensagem
     */
    public function syncTagsToMessage(Message $message, array $tagIds): void
    {
        // Validar se todas as tags existem e estão ativas
        $validTags = Tag::whereIn('id', $tagIds)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $message->tags()->sync($validTags);

        // Limpar cache relacionado
        $this->clearTagCache();
    }

    /**
     * Obter mensagens por tag
     */
    public function getMessagesByTag(Tag $tag, ?int $limit = null): Collection
    {
        $query = $tag->messages()
            ->with(['sender', 'recipient', 'messageType'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Obter mensagens por múltiplas tags
     */
    public function getMessagesByTags(array $tagIds, string $operator = 'AND', ?int $limit = null): Collection
    {
        $query = Message::query()
            ->with(['sender', 'recipient', 'messageType', 'tags']);

        if ($operator === 'AND') {
            // Mensagens que têm TODAS as tags
            foreach ($tagIds as $tagId) {
                $query->whereHas('tags', function ($q) use ($tagId) {
                    $q->where('tag_id', $tagId);
                });
            }
        } else {
            // Mensagens que têm QUALQUER uma das tags
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tag_id', $tagIds);
            });
        }

        $query->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Obter estatísticas de tags
     */
    public function getTagStatistics(): array
    {
        return Cache::remember('tag_statistics', 3600, function () {
            $stats = DB::table('tags')
                ->leftJoin('message_tags', 'tags.id', '=', 'message_tags.tag_id')
                ->select(
                    'tags.id',
                    'tags.name',
                    'tags.color',
                    'tags.rating',
                    DB::raw('COUNT(message_tags.message_id) as usage_count')
                )
                ->where('tags.is_active', true)
                ->groupBy('tags.id', 'tags.name', 'tags.color', 'tags.rating')
                ->orderBy('usage_count', 'desc')
                ->get();

            return [
                'total_tags' => Tag::where('is_active', true)->count(),
                'total_usage' => DB::table('message_tags')->count(),
                'most_used' => $stats->take(5),
                'by_rating' => $stats->groupBy('rating'),
                'average_rating' => $stats->avg('rating'),
            ];
        });
    }

    /**
     * Buscar tags por termo
     */
    public function searchTags(string $term, int $limit = 10): Collection
    {
        return Tag::where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%")
                    ->orWhere('slug', 'LIKE', "%{$term}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Obter tags mais populares
     */
    public function getPopularTags(int $limit = 10): Collection
    {
        return Cache::remember("popular_tags_{$limit}", 1800, function () use ($limit) {
            return Tag::withCount('messages')
                ->where('is_active', true)
                ->orderBy('messages_count', 'desc')
                ->orderBy('sort_order')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Obter tags por rating mínimo
     */
    public function getTagsByMinRating(int $minRating): Collection
    {
        return Tag::where('is_active', true)
            ->where('rating', '>=', $minRating)
            ->orderBy('rating', 'desc')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Obter tags de alta prioridade (rating >= 7)
     */
    public function getHighPriorityTags(): Collection
    {
        return $this->getTagsByMinRating(7);
    }

    /**
     * Criar nova tag
     */
    public function createTag(array $data): Tag
    {
        // Gerar slug se não fornecido
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        // Verificar se o slug é único
        if (Tag::where('slug', $data['slug'])->exists()) {
            throw new \InvalidArgumentException('Slug já existe.');
        }

        $tag = Tag::create($data);

        // Limpar cache
        $this->clearTagCache();

        return $tag;
    }

    /**
     * Atualizar tag
     */
    public function updateTag(Tag $tag, array $data): Tag
    {
        // Gerar slug se não fornecido
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        // Verificar se o slug é único (exceto para a própria tag)
        if (isset($data['slug']) && Tag::where('slug', $data['slug'])->where('id', '!=', $tag->id)->exists()) {
            throw new \InvalidArgumentException('Slug já existe.');
        }

        $tag->update($data);

        // Limpar cache
        $this->clearTagCache();

        return $tag->fresh();
    }

    /**
     * Deletar tag (soft delete)
     */
    public function deleteTag(Tag $tag): bool
    {
        if ($tag->isInUse()) {
            throw new \InvalidArgumentException('Não é possível deletar uma tag que está sendo usada.');
        }

        $result = $tag->delete();

        // Limpar cache
        $this->clearTagCache();

        return $result;
    }

    /**
     * Restaurar tag deletada
     */
    public function restoreTag(Tag $tag): bool
    {
        $result = $tag->restore();

        // Limpar cache
        $this->clearTagCache();

        return $result;
    }

    /**
     * Limpar cache relacionado a tags
     */
    private function clearTagCache(): void
    {
        Cache::forget('tag_statistics');

        // Limpar cache de tags populares para diferentes limites
        for ($i = 5; $i <= 20; $i += 5) {
            Cache::forget("popular_tags_{$i}");
        }
    }

    /**
     * Obter sugestões de tags baseadas no conteúdo da mensagem
     */
    public function suggestTagsForMessage(Message $message, int $limit = 5): Collection
    {
        $content = strtolower($message->content ?? '');
        $subject = strtolower($message->subject ?? '');
        $searchText = $content.' '.$subject;

        return Tag::where('is_active', true)
            ->where(function ($query) use ($searchText) {
                $query->where('name', 'LIKE', "%{$searchText}%")
                    ->orWhere('description', 'LIKE', "%{$searchText}%");
            })
            ->orderBy('rating', 'desc')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }
}
