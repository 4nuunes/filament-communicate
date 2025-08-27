<?php

declare(strict_types=1);

return [
    // Navegação
    'navigation' => [
        'message_resource' => [
            'label' => 'Mensagens',
            'group' => 'Serviços',
        ],
        'message_type_resource' => [
            'label' => 'Tipos de mensagens',
            'group' => 'Configurações',
        ],
        'tag_resource' => [
            'label' => 'Tags',
            'group' => 'Configurações',
        ],
    ],

    // Labels de modelo
    'models' => [
        'message' => [
            'label' => 'Mensagem',
            'plural_label' => 'Mensagens',
        ],
        'message_type' => [
            'label' => 'Tipo de Mensagem',
            'plural_label' => 'Tipos de Mensagem',
        ],
        'tag' => [
            'label' => 'Tag',
            'plural_label' => 'Tags',
        ],
    ],

    // Formulários
    'forms' => [
        'message' => [
            'sections' => [
                'content' => 'Conteúdo da Mensagem',
                'basic_info' => 'Informações Básicas',
                'approval_settings' => 'Configurações de Aprovação',
                'advanced_settings' => 'Configurações Avançadas',
            ],
            'fields' => [
                'recipient_id' => [
                    'label' => 'Destinatário',
                ],
                'subject' => [
                    'label' => 'Assunto',
                ],
                'content' => [
                    'label' => 'Conteúdo',
                ],
                'attachments' => [
                    'label' => 'Anexos',
                ],
                'message_type_id' => [
                    'label' => 'Tipo de Mensagem',
                ],
                'priority' => [
                    'label' => 'Prioridade',
                ],
                'save_as_draft' => [
                    'label' => 'Salvar como Rascunho',
                    'helper_text' => 'Ative para salvar como rascunho, desative para enviar imediatamente',
                ],
                'tags' => [
                    'label' => 'Tags',
                    'color' => [
                        'label' => 'Cor',
                    ],
                    'icon' => [
                        'label' => 'Ícone',
                        'hint' => 'Ver Ícones',
                    ],
                ],
            ],
        ],
        'message_type' => [
            'sections' => [
                'basic_info' => 'Informações Básicas',
                'appearance' => 'Aparência',
                'settings' => 'Configurações',
            ],
            'fields' => [
                'name' => [
                    'label' => 'Nome',
                ],
                'slug' => [
                    'label' => 'Slug',
                    'helper_text' => 'Versão amigável para URL do nome',
                ],
                'description' => [
                    'label' => 'Descrição',
                ],
                'approver_role' => [
                    'label' => 'Função Aprovador',
                ],
                'requires_approval' => [
                    'label' => 'Requer Aprovação',
                ],
                'approver_role_id' => [
                    'label' => 'Papel Aprovador',
                ],
                'custom_fields' => [
                    'label' => 'Campos Customizados',
                    'key_label' => 'Campo',
                    'value_label' => 'Valor Padrão',
                    'add_action_label' => 'Adicionar Campo',
                ],
                'is_active' => [
                    'label' => 'Ativo',
                ],
                'sort_order' => [
                    'label' => 'Ordem de Classificação',
                ],
            ],
        ],
        'approval' => [
            'reason' => [
                'label' => 'Motivo (opcional)',
            ],
        ],
        'rejection' => [
            'reason' => [
                'label' => 'Motivo da Rejeição',
            ],
        ],
        'transfer' => [
            'new_recipient_id' => [
                'label' => 'Novo Destinatário',
            ],
            'reason' => [
                'label' => 'Motivo da Transferência',
            ],
        ],
        'reply' => [
            'subject' => [
                'label' => 'Assunto',
                'prefix' => 'Re: ',
            ],
            'content' => [
                'label' => 'Conteúdo da Resposta',
            ],
        ],
        'tag' => [
            'sections' => [
                'basic_info' => 'Informações Básicas',
                'appearance' => 'Aparência',
                'settings' => 'Configurações',
            ],
            'fields' => [
                'name' => [
                    'label' => 'Nome',
                ],
                'slug' => [
                    'label' => 'Slug',
                    'helper_text' => 'Versão amigável para URL do nome',
                ],
                'color' => [
                    'label' => 'Cor',
                ],
                'icon' => [
                    'label' => 'Ícone',
                ],
                'description' => [
                    'label' => 'Descrição',
                ],
                'rating' => [
                    'label' => 'Classificação',
                ],
                'is_active' => [
                    'label' => 'Ativo',
                ],
            ],
        ],
    ],

    // Tabelas
    'tables' => [
        'columns' => [
            'subject' => 'Assunto',
            'sender' => 'Remetente',
            'recipient' => 'Destinatário',
            'code' => 'Código',
            'type' => 'Tipo',
            'priority' => 'Prioridade',
            'status' => 'Status',
            'created_at' => 'Criada em',
            'sent_at' => 'Enviada em',
            'read_at' => 'Lida em',
            'replies_count' => 'Respostas',
            'is_active' => 'Ativo',
            'name' => 'Nome',
            'approver_role' => 'Papel Aprovador',
            'requires_approval' => 'Aprovação',
            'messages_count' => 'Mensagens',
            'sort_order' => 'Ordem',
            'slug' => 'Slug',
            'description' => 'Descrição',
            'color' => 'Cor',
            'rating' => 'Classificação',
            'updated_at' => 'Atualizada em',
            'deleted_at' => 'Excluída em',
            'tags' => 'Tags',
        ],
        'placeholders' => [
            'not_read' => 'Não lida',
            'no_approver' => '—',
        ],
        'filters' => [
            'status' => [
                'label' => 'Status',
            ],
            'priority' => [
                'label' => 'Prioridade',
            ],
            'message_type' => [
                'label' => 'Tipo',
            ],
            'unread' => [
                'label' => 'Não Lidas',
            ],
            'urgent' => [
                'label' => 'Urgentes',
            ],
            'with_replies' => [
                'label' => 'Com Respostas',
            ],
            'transferable' => [
                'label' => 'Transferíveis',
            ],
            'active_status' => [
                'label' => 'Status',
                'placeholder' => 'Todos',
                'true_label' => 'Ativos',
                'false_label' => 'Inativos',
            ],
            'approval_status' => [
                'label' => 'Aprovação',
                'placeholder' => 'Todos',
                'true_label' => 'Requer Aprovação',
                'false_label' => 'Não Requer',
            ],
            'is_active' => [
                'label' => 'Status',
                'placeholder' => 'Todos',
                'true' => 'Ativos',
                'false' => 'Inativos',
            ],
            'rating' => [
                'label' => 'Classificação',
            ],
            'high_priority' => [
                'label' => 'Alta Prioridade',
            ],
            'with_messages' => [
                'label' => 'Com Mensagens',
            ],
        ],
    ],

    // Ações
    'actions' => [
        'create_message_type' => 'Novo Tipo',
        'create_message' => 'Nova Mensagem',
        'create_tag' => 'Nova Tag',
        'send_message' => 'Enviar Mensagem',
        'activate' => [
            'label' => 'Ativar',
        ],
        'deactivate' => [
            'label' => 'Desativar',
        ],
        'approve' => [
            'label' => 'Aprovar',
            'modal_heading' => 'Aprovar Mensagem',
            'modal_description' => 'Tem certeza que deseja aprovar esta mensagem?',
        ],
        'reject' => [
            'label' => 'Rejeitar',
            'modal_heading' => 'Rejeitar Mensagem',
        ],
        'transfer' => [
            'label' => 'Transferir',
            'modal_heading' => 'Transferir Mensagem',
        ],
        'reply' => [
            'label' => 'Responder',
            'modal_heading' => 'Responder Mensagem',
        ],
        'mark_read' => [
            'label' => 'Marcar como Lida',
        ],
    ],

    // Abas
    'tabs' => [
        'all' => 'Todas',
        'received' => 'Recebidas',
        'sent' => 'Enviadas',
        'unread' => 'Não Lidas',
        'pending_approval' => 'Aguardando Aprovação',
        'drafts' => 'Rascunhos',
    ],

    // Enums
    'enums' => [
        'priority' => [
            'low' => 'Baixa',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ],
        'status' => [
            'draft' => 'Rascunho',
            'pending_approval' => 'Aguardando Aprovação',
            'approved' => 'Aprovada',
            'rejected' => 'Rejeitada',
            'sent' => 'Enviada',
            'read' => 'Lida',
            'archived' => 'Arquivada',
            'new_message' => 'Nova Mensagem',
        ],
        'tag_rating' => [
            'very_low' => 'Muito Baixo',
            'low' => 'Baixo',
            'below_average' => 'Abaixo da Média',
            'below_normal' => 'Abaixo do Normal',
            'normal' => 'Normal',
            'above_normal' => 'Acima do Normal',
            'above_average' => 'Acima da Média',
            'high' => 'Alto',
            'very_high' => 'Muito Alto',
            'critical' => 'Crítico',
        ],
    ],

    // Mensagens do sistema
    'messages' => [
        'success' => [
            'message_approved' => 'Mensagem aprovada com sucesso!',
            'message_rejected' => 'Mensagem rejeitada com sucesso!',
            'message_transferred' => 'Mensagem transferida com sucesso!',
            'reply_sent' => 'Resposta enviada com sucesso!',
        ],
        'error' => [
            'approve_message' => 'Erro ao aprovar mensagem',
            'reject_message' => 'Erro ao rejeitar mensagem',
            'transfer_message' => 'Erro ao transferir mensagem',
            'send_reply' => 'Erro ao enviar resposta',
            'cannot_send_to_self' => 'Você não pode enviar mensagem para você mesmo.',
            'cannot_transfer_with_replies' => 'Não é possível transferir mensagens que possuem respostas.',
            'no_permission_to_view' => 'Você não tem permissão para visualizar esta mensagem.',
        ],
    ],

    // Templates Blade
    'view' => [
        'thread' => [
            'created_at' => 'Criada em:',
            'replied_at' => 'Respondida em:',
            'subject_prefix' => 'Assunto:',
            'read_at' => 'Lida em',
            'not_read' => 'Não lida',
            'pending_approval' => 'Aguardando aprovação',
            'message_info' => 'Informações da Mensagem',
            'approved' => 'Aprovada',
            'rejected' => 'Rejeitada',
            'pending' => 'Pendente',
            'transfers_count' => 'transferência(s)',
            'created_by' => 'Criada por',
            'recipient' => 'Destinatário:',
            'approved_by' => 'Aprovada por',
            'rejected_by' => 'Rejeitada por',
            'awaiting_approval' => 'Aguardando aprovação',
            'transferred_from_to' => 'Transferida de',
            'to' => 'para',
            'reason' => 'Motivo:',
            'current_responsible' => 'Responsável atual:',
            'current' => 'Atual',
            'replies_in_thread' => 'resposta(s) neste thread',
            'current_responsible_footer' => 'Responsável atual:',
            'created_at_footer' => 'Criada em',
        ],
    ],

    // Validações
    'validation' => [
        'message_type_name_required' => 'O nome do tipo de mensagem é obrigatório.',
        'only_recipient_can_mark_read' => 'Apenas o destinatário pode marcar a mensagem como lida.',
        'cannot_send_to_self' => 'Você não pode enviar mensagem para você mesmo.',
    ],

    'logs' => [
        'message_created_without_type' => 'Mensagem criada sem tipo definido',
        'message_marked_as_read' => 'Mensagem marcada como lida',
        'message_converted_to_approval' => 'Mensagem convertida para aprovação necessária',
    ],

    // Notificações
    'notifications' => [
        'mail' => [
            'greeting' => 'Olá :name!',
            'footer' => 'Obrigado por usar nosso sistema de mensagens.',
        ],
        'actions' => [
            'view_message' => 'Ver Mensagem',
            'view_reply' => 'Ver Resposta',
            'mark_as_read' => 'Marcar como Lida',
            'dismiss' => 'Dispensar',
        ],
        'new_message' => [
            'title' => 'Nova Mensagem',
            'body' => 'Você recebeu uma nova mensagem de :sender_name: ":subject"',
        ],
        'reply' => [
            'title' => 'Nova Resposta',
            'body' => ':sender_name respondeu à mensagem ":subject"',
        ],
        'pending_approval' => [
            'title' => 'Mensagem Aguardando Aprovação',
            'body' => ':sender_name enviou uma mensagem que precisa de aprovação: ":subject"',
        ],
        'approved' => [
            'title' => 'Mensagem Aprovada',
            'body' => 'Sua mensagem ":subject" foi aprovada e enviada.',
        ],
        'rejected' => [
            'title' => 'Mensagem Rejeitada',
            'body' => 'Sua mensagem ":subject" foi rejeitada.',
            'reason' => 'Motivo: :reason',
        ],
        'transferred' => [
            'title' => 'Mensagem Transferida',
            'body' => 'A mensagem ":subject" foi transferida para você.',
        ],
        'transferred_info' => [
            'title' => 'Mensagem Transferida',
            'body' => 'Sua mensagem ":subject" foi transferida para outro usuário.',
        ],
        'default' => [
            'title' => 'Notificação',
            'body' => 'Você tem uma nova notificação sobre a mensagem ":subject"',
        ],
    ],

    // Exceções
    'exceptions' => [
        'cannot_reply_to_message' => 'Usuário não pode responder a esta mensagem',
        'cannot_redeliver_invalid_status' => 'Mensagem não pode ser reentregue - status inválido',
        'only_pending_can_be_approved' => 'Apenas mensagens pendentes podem ser aprovadas',
        'no_permission_to_approve' => 'Usuário não tem permissão para aprovar esta mensagem',
        'message_not_pending_approval' => 'Mensagem não está pendente de aprovação',
        'cannot_reject_own_message' => 'Você não pode rejeitar sua própria mensagem',
        'not_authorized_to_reject' => 'Usuário não autorizado a rejeitar esta mensagem',
        'no_approver_found' => 'Nenhum aprovador encontrado para este tipo de mensagem',
        'no_alternative_approver_found' => 'Nenhum aprovador alternativo encontrado',
        'cannot_transfer_current_status' => 'Mensagem não pode ser transferida no status atual',
        'only_current_recipient_can_transfer' => 'Apenas o destinatário atual pode transferir a mensagem',
        'cannot_transfer_to_self' => 'Não é possível transferir mensagem para você mesmo',
    ],
];
