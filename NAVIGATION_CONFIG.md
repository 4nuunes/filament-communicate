# Configuração de Navegação do Plugin Filament Communicate

Este plugin permite customizar as configurações de navegação dos recursos através do arquivo de configuração.

## Configuração

As configurações de navegação estão localizadas no arquivo `config/filament-communicate.php` na seção `navigation`:

```php
'navigation' => [
    // Configurações para MessageResource
    'message_resource' => [
        'group' => 'Serviços',
        'sort' => 40,
        'icon' => 'heroicon-o-envelope',
    ],
    
    // Configurações para MessageTypeResource
    'message_type_resource' => [
        'group' => 'Configurações',
        'sort' => 13,
        'icon' => 'heroicon-o-rectangle-group',
        'label' => 'Tipos de mensagens',
    ],
],
```

## Opções Disponíveis

### MessageResource
- `group`: Grupo de navegação no menu lateral
- `sort`: Ordem de exibição no menu
- `icon`: Ícone do menu (Heroicons)

### MessageTypeResource
- `group`: Grupo de navegação no menu lateral
- `sort`: Ordem de exibição no menu
- `icon`: Ícone do menu (Heroicons)
- `label`: Rótulo customizado para o menu

## Exemplos de Customização

### Alterar grupo de navegação
```php
'navigation' => [
    'message_resource' => [
        'group' => 'Comunicação',
        'sort' => 10,
        'icon' => 'heroicon-o-chat-bubble-left-right',
    ],
],
```

### Alterar ordem de exibição
```php
'navigation' => [
    'message_type_resource' => [
        'group' => 'Sistema',
        'sort' => 5,
        'icon' => 'heroicon-o-cog-6-tooth',
        'label' => 'Tipos de Mensagem',
    ],
],
```

## Publicar Configuração

Para customizar essas configurações, publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag="filament-communicate-config"
```

Depois edite o arquivo `config/filament-communicate.php` conforme necessário.

## Valores Padrão

Se uma configuração não for definida, os valores padrão serão utilizados:

- **MessageResource**: Grupo "Serviços", Sort 40, Ícone "heroicon-o-envelope"
- **MessageTypeResource**: Grupo "Configurações", Sort 13, Ícone "heroicon-o-rectangle-group", Label "Tipos de mensagens"