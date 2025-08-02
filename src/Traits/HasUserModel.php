<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Traits;

trait HasUserModel
{
    /**
     * Obtém o modelo de usuário configurado para o plugin
     *
     * Primeiro verifica se há uma configuração específica no plugin,
     * caso contrário usa a configuração padrão do auth do Laravel.
     */
    protected function getUserModel(): string
    {
        return config('filament-communicate.user_model')
            ?? config('auth.providers.users.model');
    }
}
