# Solução para Problemas do Plugin Filament Communicate

## Problemas Identificados

### 1. Usuários não podem excluir mensagens criadas

**Problema:** O sistema não permite que usuários excluam mensagens que eles próprios criaram, mesmo quando apropriado.

### 2. Grupo de navegação não respeita configuração

**Problema:** O grupo de navegação está usando tradução fixa ao invés da configuração definida em `config/filament-communicate.php`.

## Análise Técnica

### Problema 1: Exclusão de Mensagens

**Situação Atual:**

* O `MessageResource` possui `DeleteAction` e `DeleteBulkAction` configurados

* Não há Policy específica implementada para controlar permissões de exclusão

* As regras de negócio para exclusão não estão claramente definidas

**Regras de Negócio Propostas:**

1. **Rascunhos (DRAFT):** Podem ser excluídos pelo autor a qualquer momento
2. **Mensagens Enviadas (SENT/READ):** Podem ser excluídas pelo autor apenas se não tiverem respostas
3. **Mensagens Pendentes (PENDING):** Podem ser excluídas pelo autor
4. **Mensagens Aprovadas/Rejeitadas:** Não podem ser excluídas (preservar histórico)
5. **Super Admins:** Podem excluir qualquer mensagem
6. **Supervisores:** Podem excluir mensagens pendentes que não sejam suas

### Problema 2: Grupo de Navegação

**Situação Atual:**

```php
// Em MessageResource.php linha ~35
public static function getNavigationGroup(): ?string
{
    return __('filament-communicate::default.navigation.message_resource.group');
}
```

**Problema:** Está usando tradução fixa ao invés da configuração.

**Configuração Atual:**

```php
// config/filament-communicate.php linha 142
'navigation' => [
    'message_resource' => [
        'group' => 'Comunicação',
        // ...
    ],
],
```

## Soluções Implementadas

### Solução 1: Implementar Policy para Mensagens

**Arquivo:** `src/Policies/MessagePolicy.php`

```php
<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Policies;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Illuminate\Foundation\Auth\User as Authenticatable;

class MessagePolicy
{
    /**
     * Determina se o usuário pode excluir a mensagem
     */
    public function delete(?Authenticatable $user, Message $message): bool
    {
        if (!$user) {
            return false;
        }

        // Super admins podem excluir qualquer mensagem
        if (MessagePermissions::isSuperAdmin($user)) {
            return true;
        }

        // Supervisores podem excluir mensagens pendentes que não sejam suas
        if (MessagePermissions::isSupervisor($user)) {
            return $this->canSupervisorDelete($user, $message);
        }

        // Usuários comuns só podem excluir suas próprias mensagens
        return $this->canUserDelete($user, $message);
    }

    /**
     * Determina se o usuário pode forçar exclusão (hard delete)
     */
    public function forceDelete(?Authenticatable $user, Message $message): bool
    {
        if (!$user) {
            return false;
        }

        // Apenas super admins podem fazer hard delete
        return MessagePermissions::isSuperAdmin($user);
    }

    /**
     * Determina se o usuário pode restaurar mensagem excluída
     */
    public function restore(?Authenticatable $user, Message $message): bool
    {
        if (!$user) {
            return false;
        }

        // Super admins e supervisores podem restaurar
        return MessagePermissions::isSuperAdmin($user) || 
               MessagePermissions::isSupervisor($user);
    }

    /**
     * Regras para supervisores excluírem mensagens
     */
    private function canSupervisorDelete(Authenticatable $user, Message $message): bool
    {
        // Não pode excluir próprias mensagens usando privilégios de supervisor
        if ($message->sender_id === $user->id) {
            return $this->canUserDelete($user, $message);
        }

        // Pode excluir mensagens pendentes
        return $message->status === MessageStatus::PENDING;
    }

    /**
     * Regras para usuários comuns excluírem suas mensagens
     */
    private function canUserDelete(Authenticatable $user, Message $message): bool
    {
        // Só pode excluir próprias mensagens
        if ($message->sender_id !== $user->id) {
            return false;
        }

        // Regras baseadas no status
        switch ($message->status) {
            case MessageStatus::DRAFT:
                // Rascunhos podem sempre ser excluídos
                return true;

            case MessageStatus::PENDING:
                // Mensagens pendentes podem ser excluídas
                return true;

            case MessageStatus::SENT:
            case MessageStatus::READ:
                // Mensagens enviadas/lidas só podem ser excluídas se não tiverem respostas
                return $message->replies()->count() === 0;

            case MessageStatus::APPROVED:
            case MessageStatus::REJECTED:
                // Mensagens aprovadas/rejeitadas não podem ser excluídas (preservar histórico)
                return false;

            default:
                return false;
        }
    }
}
```

### Solução 2: Registrar Policy no Service Provider

**Arquivo:** `src/FilamentCommunicateServiceProvider.php`

**Adicionar no método** **`boot()`:**

```php
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Policies\MessagePolicy;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // ... código existente ...

    // Registrar Policy para Message
    Gate::policy(Message::class, MessagePolicy::class);
}
```

### Solução 3: Corrigir Grupo de Navegação

**Arquivo:** `src/Resources/MessageResource.php`

**Modificar o método** **`getNavigationGroup()`:**

```php
public static function getNavigationGroup(): ?string
{
    // Primeiro tenta usar a configuração, depois fallback para tradução
    $configGroup = config('filament-communicate.navigation.message_resource.group');
    
    if ($configGroup) {
        return $configGroup;
    }
    
    // Fallback para tradução se não houver configuração
    return __('filament-communicate::default.navigation.message_resource.group');
}
```

**Aplicar a mesma correção para** **`MessageTypeResource.php`:**

```php
public static function getNavigationGroup(): ?string
{
    // Primeiro tenta usar a configuração, depois fallback para tradução
    $configGroup = config('filament-communicate.navigation.message_type_resource.group');
    
    if ($configGroup) {
        return $configGroup;
    }
    
    // Fallback para tradução se não houver configuração
    return __('filament-communicate::default.navigation.message_type_resource.group');
}
```

### Solução 4: Melhorar Visibilidade das Ações de Exclusão

**Arquivo:** `src/Resources/MessageResource.php`

**Modificar as ações na tabela para usar a Policy:**

```php
// Na seção de actions, modificar DeleteAction
Tables\Actions\DeleteAction::make()
    ->visible(fn ($record) => auth()->user()->can('delete', $record)),
```

**E nas bulk actions:**

```php
// Na seção de bulkActions
Tables\Actions\DeleteBulkAction::make()
    ->visible(fn () => auth()->user()->can('delete', static::getModel())),
Tables\Actions\RestoreBulkAction::make()
    ->visible(fn () => auth()->user()->can('restore', static::getModel())),
Tables\Actions\ForceDeleteBulkAction::make()
    ->visible(fn () => auth()->user()->can('forceDelete', static::getModel())),
```

## Implementação Passo a Passo

### Passo 1: Criar a Policy

1. Criar o arquivo `src/Policies/MessagePolicy.php` com o código fornecido
2. Implementar as regras de negócio conforme especificado

### Passo 2: Registrar a Policy

1. Modificar `src/FilamentCommunicateServiceProvider.php`
2. Adicionar o registro da Policy no método `boot()`

### Passo 3: Corrigir Navegação

1. Modificar `src/Resources/MessageResource.php`
2. Modificar `src/Resources/MessageTypeResource.php`
3. Atualizar os métodos `getNavigationGroup()`

### Passo 4: Atualizar Ações da Tabela

1. Modificar as ações de exclusão para usar a Policy
2. Adicionar visibilidade condicional baseada em permissões

### Passo 5: Testar

1. Testar exclusão com diferentes tipos de usuário
2. Verificar se o grupo de navegação está usando a configuração
3. Validar regras de negócio para diferentes status de mensagem

## Considerações Adicionais

### Segurança

* A Policy garante que apenas usuários autorizados possam excluir mensagens

* Hard delete é restrito apenas a super admins

* Mensagens com histórico importante (aprovadas/rejeitadas) são preservadas

### Usabilidade

* Ações de exclusão só aparecem quando o usuário tem permissão

* Mensagens com respostas não podem ser excluídas para manter integridade do thread

* Rascunhos podem sempre ser excluídos pelo autor

### Manutenibilidade

* Configuração centralizada no arquivo config

* Fallback para traduções mantém compatibilidade

* Policy separada facilita manutenção das regras de negócio

## Resultado Esperado

Após implementar essas soluções:

1. **Exclusão de Mensagens:** Usuários poderão excluir suas mensagens seguindo regras de negócio claras e seguras
2. **Grupo de Navegação:** O grupo será definido pela configuração em `config/filament-communicate.php`
3. **Segurança:** Sistema manterá integridade dos dados e histórico importante
4. **Flexibilidade:** Administradores podem ajustar configurações sem modificar código

