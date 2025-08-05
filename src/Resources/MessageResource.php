<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources;

use Alessandronuunes\FilamentCommunicate\Enums\MessagePriority;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource\Pages;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use Alessandronuunes\FilamentCommunicate\Traits\HasUserModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MessageResource extends Resource
{
    use HasUserModel;

    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 40;

    public static function getNavigationIcon(): ?string
    {
        return config('filament-communicate.navigation.message_resource.icon', static::$navigationIcon);
    }

    public static function getNavigationGroup(): ?string
    {
        // Priorizar configuração do arquivo config
        $configGroup = config('filament-communicate.navigation.message_resource.group');

        if ($configGroup !== null) {
            return $configGroup;
        }

        // Fallback para tradução se não configurado
        return __('filament-communicate::default.navigation.message_resource.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-communicate::default.navigation.message_resource.label');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-communicate.navigation.message_resource.sort', static::$navigationSort);
    }

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getModelLabel(): string
    {
        return __('filament-communicate::default.models.message.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-communicate::default.models.message.plural_label');
    }

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament-communicate::default.forms.message.sections.content'))
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('recipient_id')
                            ->label(__('filament-communicate::default.forms.message.fields.recipient_id.label'))
                            ->relationship('recipient', 'name', function (Builder $query) {
                                $query->where('id', '!=', Auth::id());
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} ({$record->email})")
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if ($value == Auth::id()) {
                                            $fail(__('filament-communicate::default.validation.cannot_send_to_self'));
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\TextInput::make('subject')
                            ->label(__('filament-communicate::default.forms.message.fields.subject.label'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content')
                            ->label(__('filament-communicate::default.forms.message.fields.content.label'))
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'undo',
                                'redo',
                            ]),

                        Forms\Components\FileUpload::make('attachments')
                            ->label(__('filament-communicate::default.forms.message.fields.attachments.label'))
                            ->multiple()
                            ->maxFiles(config('filament-communicate.storage.max_files', 5))
                            ->maxSize(config('filament-communicate.storage.max_file_size', 10240))
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Select::make('message_type_id')
                            ->label(__('filament-communicate::default.forms.message.fields.message_type_id.label'))
                            ->relationship('messageType', 'name')
                            ->required()
                            ->searchable()

                            ->columnSpan(2)
                            ->preload(),

                        Forms\Components\Select::make('priority')
                            ->label(__('filament-communicate::default.forms.message.fields.priority.label'))
                            ->options(MessagePriority::class)
                            ->default(MessagePriority::NORMAL)

                            ->columnSpan(2)
                            ->required(),

                        Forms\Components\Toggle::make('save_as_draft')
                            ->label(__('filament-communicate::default.forms.message.fields.save_as_draft.label'))
                            ->helperText(__('filament-communicate::default.forms.message.fields.save_as_draft.helper_text'))
                            ->default(false)
                            ->columnSpanFull()
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, $get) {
                                if ($get('status') === MessageStatus::DRAFT->value) {
                                    $component->state(true);
                                }
                            })->live(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateActions([])
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('filament-communicate::default.tables.columns.subject'))
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight(function (Message $record): FontWeight {
                        // Negrito para mensagens não lidas pelo usuário atual
                        return $record->read_at === null
                            ? FontWeight::Bold
                            : FontWeight::Light;
                    })
                    ->formatStateUsing(function (string $state, Message $record): string {
                        $prefix = '';

                        // Adicionar badge "NOVA" para mensagens não lidas
                        if ($record->read_at === null && $record->recipient_id === Auth::id()) {
                            $prefix = '🔴 ';
                        }

                        if ($record->isReply()) {
                            return $prefix.'↳ Re: '.$state;
                        }

                        return $prefix.$state;
                    })
                    ->html() // Permitir HTML para renderizar o emoji
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('sender.name')
                    ->label(__('filament-communicate::default.tables.columns.sender'))
                    ->searchable()
                    ->sortable()
                    ->weight(function (Message $record): FontWeight {
                        // Negrito para mensagens não lidas pelo usuário atual
                        return $record->read_at === null
                            ? FontWeight::Bold
                            : FontWeight::Light;
                    }),

                Tables\Columns\TextColumn::make('recipient.name')
                    ->label(__('filament-communicate::default.tables.columns.recipient'))
                    ->searchable()
                    ->sortable()
                    ->weight(function (Message $record): FontWeight {
                        // Negrito para mensagens não lidas pelo usuário atual
                        return $record->read_at === null
                            ? FontWeight::Bold
                            : FontWeight::Light;
                    }),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('filament-communicate::default.tables.columns.code'))
                    ->searchable()
                    ->sortable()
                    ->weight(function (Message $record): FontWeight {
                        // Negrito para mensagens não lidas pelo usuário atual
                        return $record->read_at === null
                            ? FontWeight::Bold
                            : FontWeight::Light;
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                Tables\Columns\TextColumn::make('messageType.name')
                    ->label(__('filament-communicate::default.tables.columns.type'))
                    ->badge()
                    ->color(fn ($record) => $record->messageType->color ?? 'gray'),

                Tables\Columns\TextColumn::make('priority')
                    ->label(__('filament-communicate::default.tables.columns.priority'))
                    ->badge()
                    ->color(fn (MessagePriority $state): string => match ($state) {
                        MessagePriority::URGENT => 'danger',
                        MessagePriority::HIGH => 'warning',
                        MessagePriority::NORMAL => 'success',
                        MessagePriority::LOW => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-communicate::default.tables.columns.status'))
                    ->badge()
                    ->formatStateUsing(function (MessageStatus $state, Message $record): string {
                        return $state->getLabelForUser(Auth::user(), $record);
                    })
                    ->color(function (MessageStatus $state, Message $record): string {
                        return $state->getColorForUser(Auth::user(), $record);
                    })
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-communicate::default.tables.columns.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight(function (Message $record): FontWeight {
                        // Negrito para mensagens não lidas pelo usuário atual
                        return $record->read_at === null
                            ? FontWeight::Bold
                            : FontWeight::Light;
                    }),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('filament-communicate::default.tables.columns.sent_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Informações de leitura
                Tables\Columns\TextColumn::make('read_at')
                    ->label(__('filament-communicate::default.tables.columns.read_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder(__('filament-communicate::default.tables.placeholders.not_read'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Contador de respostas (mantido apenas para referência)
                Tables\Columns\TextColumn::make('replies_count')
                    ->label(__('filament-communicate::default.tables.columns.replies_count'))
                    ->counts('replies')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-communicate::default.tables.filters.status.label'))
                    ->options(MessageStatus::class),

                Tables\Filters\SelectFilter::make('priority')
                    ->label(__('filament-communicate::default.tables.filters.priority.label'))
                    ->options(MessagePriority::class),

                Tables\Filters\SelectFilter::make('message_type_id')
                    ->label(__('filament-communicate::default.tables.filters.message_type.label'))
                    ->relationship('messageType', 'name'),

                Tables\Filters\Filter::make('unread')
                    ->label(__('filament-communicate::default.tables.filters.unread.label'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('read_at')),

                Tables\Filters\Filter::make('urgent')
                    ->label(__('filament-communicate::default.tables.filters.urgent.label'))
                    ->query(fn (Builder $query): Builder => $query->where('priority', MessagePriority::URGENT)), // Corrigido de URGENT

                Tables\Filters\Filter::make('with_replies')
                    ->label(__('filament-communicate::default.tables.filters.with_replies.label'))
                    ->query(fn (Builder $query): Builder => $query->has('replies')),

                Tables\Filters\Filter::make('transferable')
                    ->label(__('filament-communicate::default.tables.filters.transferable.label'))
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [MessageStatus::SENT, MessageStatus::READ])
                        ->where('recipient_id', Auth::id())
                        ->doesntHave('replies')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === MessageStatus::DRAFT),
                    Tables\Actions\Action::make('approve')
                        ->label(__('filament-communicate::default.actions.approve.label'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(
                            fn ($record) => $record->status === MessageStatus::PENDING &&
                            $record->sender_id !== Auth::id() // Não pode aprovar própria mensagem
                        )
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label(__('filament-communicate::default.forms.approval.reason.label'))
                                ->maxLength(500),
                        ])
                        ->action(function (Message $record, array $data) {
                            app(MessageService::class)
                                ->approveMessage($record, Auth::user(), $data['reason'] ?? null);
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label(__('filament-communicate::default.actions.reject.label'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(
                            fn ($record) => $record->status === MessageStatus::PENDING &&
                            $record->sender_id !== Auth::id()
                        )
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label(__('filament-communicate::default.forms.rejection.reason.label'))
                                ->required()
                                ->maxLength(500),
                        ])
                        ->action(function (Message $record, array $data) {
                            app(MessageService::class)
                                ->rejectMessage($record, Auth::user(), $data['reason']);
                        }),

                    Tables\Actions\Action::make('transfer')
                        ->label(__('filament-communicate::default.actions.transfer.label'))
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('info')
                        ->visible(function ($record) {
                            // Só pode transferir se:
                            // 1. Status é SENT ou READ
                            // 2. Usuário atual é o destinatário
                            // 3. Mensagem NÃO possui respostas
                            return in_array($record->status, [MessageStatus::SENT, MessageStatus::READ]) &&
                                   $record->recipient_id === Auth::id() &&
                                   $record->replies()->count() === 0;
                        })
                        ->form([
                            Forms\Components\Select::make('new_recipient_id')
                                ->label(__('filament-communicate::default.forms.transfer.new_recipient_id.label'))
                                ->relationship('recipient', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Textarea::make('reason')
                                ->label(__('filament-communicate::default.forms.transfer.reason.label'))
                                ->maxLength(500),
                        ])
                        ->action(function (Message $record, array $data) {
                            // Verificação adicional antes da transferência
                            if ($record->replies()->count() > 0) {
                                throw new \Exception(__('filament-communicate::default.messages.error.cannot_transfer_with_replies'));
                            }

                            $userModel = app(static::class)->getUserModel();
                            $newRecipient = $userModel::find($data['new_recipient_id']);
                            app(MessageService::class)
                                ->transferMessage($record, $newRecipient, Auth::user(), $data['reason'] ?? null);
                        }),

                    Tables\Actions\Action::make('mark_read')
                        ->label(__('filament-communicate::default.actions.mark_read.label'))
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->visible(fn ($record) => ! $record->read_at && $record->recipient_id === Auth::id())
                        ->action(function (Message $record) {
                            app(MessageService::class)
                                ->markAsRead($record, Auth::user());
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->authorize('delete'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->authorize('deleteAny'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->authorize('restoreAny'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->authorize('forceDeleteAny'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'view' => Pages\ViewMessage::route('/{record}'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'sender',
                'recipient',
                'currentRecipient',
                'messageType',
                'latestApproval.approver',
                'transfers.transferredBy',
                'replies',
            ])
            ->withCount('replies');

        // Aplicar filtros baseados nas permissões do usuário
        return MessagePermissions::applyQueryFilters($query, Auth::user());
    }

    public static function getNavigationBadge(): ?string
    {
        // Badge com contagem de mensagens não lidas para o usuário atual
        $count = static::getModel()::where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }
}
