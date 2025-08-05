<?php

declare(strict_types=1);

return [

    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Modelo de Usuário
    |--------------------------------------------------------------------------
    |
    | Define qual modelo será usado para os relacionamentos com usuários.
    | Por padrão, usa o modelo configurado no auth, mas pode ser customizado.
    |
    */
    'user_model' => config('auth.providers.users.model'),

    /*
    |--------------------------------------------------------------------------
    | Configurações de Permissões de Mensagens
    |--------------------------------------------------------------------------
    |
    | Define quais roles têm acesso a diferentes funcionalidades do sistema
    | de mensagens baseado nas funções dos usuários.
    |
    */

    // Roles que têm acesso total a todas as mensagens
    'super_admin_roles' => [
        'super_admin',
    ],

    // Roles que podem aprovar mensagens (supervisores)
    'supervisor_roles' => [
        'supervisor',
    ],

    // Roles que podem apenas ver suas próprias mensagens
    'user_roles' => [
        'atendente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Regras de Visibilidade
    |--------------------------------------------------------------------------
    |
    | Define quando um supervisor pode ver uma mensagem:
    | - Apenas mensagens com status PENDING
    | - Apenas se não for o remetente da mensagem
    |
    */
    'supervisor_visibility' => [
        'allowed_statuses' => [
            Alessandronuunes\FilamentCommunicate\Enums\MessageStatus::PENDING,
        ],
        'exclude_own_messages' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Storage
    |--------------------------------------------------------------------------
    |
    | Define as configurações de armazenamento para anexos de mensagens.
    | Usa os discos já configurados no sistema (public, s3, etc.)
    |
    */
    'storage' => [
        // Disco usado para armazenar anexos de mensagens
        // Opções: 'public', 's3', 'local' (conforme configurado em config/filesystems.php)
        'disk' => env('COMMUNICATE_STORAGE_DISK', 'public'),

        // Diretório dentro do disco para armazenar anexos
        'directory' => 'messages/attachments',

        // Tamanho máximo por arquivo em KB (10MB = 10240KB)
        'max_file_size' => 10240,

        // Número máximo de arquivos por mensagem
        'max_files' => 5,

        // Tipos de arquivo permitidos
        'allowed_file_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Código de Mensagem
    |--------------------------------------------------------------------------
    |
    | Define o formato e estrutura dos códigos únicos das mensagens.
    | Formato padrão: {prefix}-{year}-{sequence}
    |
    */
    'message_code' => [
        // Prefixo do código (ex: VMIX, MSG, etc.)
        'prefix' => env('COMMUNICATE_CODE_PREFIX', 'MSG'),

        // Separador entre as partes do código
        'separator' => '-',

        // Incluir ano no código
        'include_year' => false,

        // Formato do ano (Y = 4 dígitos, y = 2 dígitos)
        'year_format' => 'Y',

        // Número de dígitos para a sequência (com zeros à esquerda)
        'sequence_digits' => 6,

        // Resetar sequência a cada ano
        'reset_yearly' => false,

        // Formato customizado (opcional)
        // Se definido, sobrescreve as configurações acima
        // Variáveis disponíveis: {prefix}, {year}, {sequence}
        'custom_format' => null, // ex: '{prefix}{year}{sequence}' ou '{prefix}-{year}-{sequence}'
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Navegação do Filament
    |--------------------------------------------------------------------------
    |
    | Define as configurações de navegação para os recursos do plugin,
    | incluindo grupos de navegação e ordem de exibição.
    |
    */
    'navigation' => [
        // Configurações para MessageResource
        'message_resource' => [
            'group' => 'Comunicação',
            'sort' => 40,
            'icon' => 'heroicon-o-envelope',
        ],

        // Configurações para MessageTypeResource
        'message_type_resource' => [
            'group' => 'Comunicação',
            'sort' => 13,
            'icon' => 'heroicon-o-rectangle-group',
            'label' => 'Tipos de mensagens',
        ],
    ],
];
