<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources;

use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class MessageTypeResource extends Resource
{
    protected static ?string $model = MessageType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 13;

    public static function getNavigationIcon(): ?string
    {
        return config('filament-communicate.navigation.message_type_resource.icon', static::$navigationIcon);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-communicate::default.navigation.message_type_resource.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-communicate.navigation.message_type_resource.sort', static::$navigationSort);
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-communicate::default.navigation.message_type_resource.label');
    }

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getModelLabel(): string
    {
        return __('filament-communicate::default.models.message_type.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-communicate::default.models.message_type.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-communicate::default.forms.message_type.fields.name.label'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->live(onBlur: true),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament-communicate::default.forms.message_type.fields.description.label'))
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')
                    ->label(__('filament-communicate::default.forms.message_type.fields.sort_order.label'))
                    ->numeric()
                    ->default(0)
                    ->columnSpanFull()
                    ->minValue(0),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('filament-communicate::default.forms.message_type.fields.is_active.label'))
                    ->default(true),
                Forms\Components\Toggle::make('requires_approval')
                    ->label(__('filament-communicate::default.forms.message_type.fields.requires_approval.label'))
                    ->default(false)
                    ->live(),
                Forms\Components\Select::make('approver_role_id')
                    ->label(__('filament-communicate::default.forms.message_type.fields.approver_role.label'))
                    ->options(Role::pluck('name', 'id'))
                    ->columnSpanFull()
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get): bool => $get('requires_approval') ?? false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament-communicate::default.tables.columns.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-communicate::default.tables.columns.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approverRole.name')
                    ->label(__('filament-communicate::default.tables.columns.approver_role'))
                    ->placeholder(__('filament-communicate::default.tables.placeholders.no_approver')),
                Tables\Columns\IconColumn::make('requires_approval')
                    ->label(__('filament-communicate::default.tables.columns.requires_approval'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label(__('filament-communicate::default.tables.columns.messages_count'))
                    ->counts('messages')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('filament-communicate::default.tables.columns.sort_order'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-communicate::default.tables.columns.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament-communicate::default.tables.filters.active_status.label'))
                    ->placeholder(__('filament-communicate::default.tables.filters.active_status.placeholder'))
                    ->trueLabel(__('filament-communicate::default.tables.filters.active_status.true_label'))
                    ->falseLabel(__('filament-communicate::default.tables.filters.active_status.false_label')),

                Tables\Filters\TernaryFilter::make('requires_approval')
                    ->label(__('filament-communicate::default.tables.filters.approval_status.label'))
                    ->placeholder(__('filament-communicate::default.tables.filters.approval_status.placeholder'))
                    ->trueLabel(__('filament-communicate::default.tables.filters.approval_status.true_label'))
                    ->falseLabel(__('filament-communicate::default.tables.filters.approval_status.false_label')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
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
            'index' => Pages\ListMessageTypes::route('/'),
            // 'create' => Pages\CreateMessageType::route('/create'),
            // 'edit' => Pages\EditMessageType::route('/{record}/edit'),
        ];
    }
}
