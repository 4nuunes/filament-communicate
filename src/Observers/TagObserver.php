<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Observers;

use Alessandronuunes\FilamentCommunicate\Models\Tag;
use Illuminate\Support\Str;

class TagObserver
{
    /**
     * Executado antes de criar uma nova tag.
     */
    public function creating(Tag $tag): void
    {
        // Gera slug automaticamente se não fornecido
        if (empty($tag->slug)) {
            $tag->slug = Str::slug($tag->name);
        }
    }

    /**
     * Executado antes de atualizar uma tag.
     */
    public function updating(Tag $tag): void
    {
        // Atualiza slug se o nome for alterado ou se o slug estiver vazio
        if ($tag->isDirty('name') || empty($tag->slug)) {
            $tag->slug = Str::slug($tag->name);
        }
    }

    /**
     * Executado antes de salvar (criar ou atualizar).
     */
    public function saving(Tag $tag): void
    {
        // Garante que sempre haverá um slug válido
        if (empty($tag->slug) && ! empty($tag->name)) {
            $tag->slug = Str::slug($tag->name);
        }
    }
}
