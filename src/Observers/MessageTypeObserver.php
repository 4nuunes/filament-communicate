<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Observers;

use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Illuminate\Support\Str;

class MessageTypeObserver
{
    /**
     * Handle the MessageType "creating" event.
     * Gera o slug automaticamente antes de criar o registro
     */
    public function creating(MessageType $messageType): void
    {
        if (empty($messageType->slug)) {
            $messageType->slug = $this->generateUniqueSlug($messageType->name);
        }
    }

    /**
     * Handle the MessageType "updating" event.
     * Atualiza o slug se o nome foi alterado e o slug está vazio
     */
    public function updating(MessageType $messageType): void
    {
        // Se o nome foi alterado e o slug não foi definido manualmente
        if ($messageType->isDirty('name') && empty($messageType->slug)) {
            $messageType->slug = $this->generateUniqueSlug($messageType->name, $messageType->id);
        }

        // Se o slug foi alterado, garantir que seja único
        if ($messageType->isDirty('slug') && ! empty($messageType->slug)) {
            $messageType->slug = $this->generateUniqueSlug($messageType->slug, $messageType->id);
        }
    }

    /**
     * Gera um slug único baseado no texto fornecido
     *
     * @param  string  $text  Texto base para gerar o slug
     * @param  int|null  $ignoreId  ID do registro a ser ignorado na verificação de unicidade
     * @return string Slug único gerado
     */
    private function generateUniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($text);
        $slug = $baseSlug;
        $counter = 1;

        // Verificar se o slug já existe
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Verifica se um slug já existe na base de dados
     *
     * @param  string  $slug  Slug a ser verificado
     * @param  int|null  $ignoreId  ID do registro a ser ignorado
     * @return bool True se o slug já existe
     */
    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = MessageType::where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Handle the MessageType "saving" event.
     * Validação adicional antes de salvar
     */
    public function saving(MessageType $messageType): void
    {
        // Garantir que o slug não contenha caracteres inválidos
        if (! empty($messageType->slug)) {
            $messageType->slug = Str::slug($messageType->slug);
        }

        // Garantir que o nome não seja vazio
        if (empty($messageType->name)) {
            throw new \InvalidArgumentException(__('filament-communicate::default.validation.message_type_name_required'));
        }
    }

    /**
     * Handle the MessageType "deleted" event.
     * Log quando um tipo de mensagem é excluído
     */
    public function deleted(MessageType $messageType): void
    {
        \Log::info('MessageType deleted', [
            'id' => $messageType->id,
            'name' => $messageType->name,
            'slug' => $messageType->slug,
        ]);
    }

    /**
     * Handle the MessageType "restored" event.
     * Log quando um tipo de mensagem é restaurado
     */
    public function restored(MessageType $messageType): void
    {
        \Log::info('MessageType restored', [
            'id' => $messageType->id,
            'name' => $messageType->name,
            'slug' => $messageType->slug,
        ]);
    }
}
