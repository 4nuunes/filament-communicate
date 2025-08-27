<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources;

use Alessandronuunes\FilamentCommunicate\Models\Tag;
use Alessandronuunes\FilamentCommunicate\Resources\TagResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 50;

    public static function getNavigationIcon(): ?string
    {
        return config('filament-communicate.navigation.tag_resource.icon', static::$navigationIcon);
    }

    public static function getNavigationGroup(): ?string
    {
        // Priorizar configuração do arquivo config
        $configGroup = config('filament-communicate.navigation.tag_resource.group');

        if ($configGroup !== null) {
            return $configGroup;
        }

        // Fallback para tradução se não configurado
        return __('filament-communicate::default.navigation.tag_resource.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-communicate::default.navigation.tag_resource.label');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-communicate.navigation.tag_resource.sort', static::$navigationSort);
    }

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getModelLabel(): string
    {
        return __('filament-communicate::default.models.tag.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-communicate::default.models.tag.plural_label');
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament-communicate::default.forms.tag.sections.basic_info'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-communicate::default.forms.tag.fields.name.label'))
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament-communicate::default.forms.tag.fields.slug.label'))
                            ->required()
                            ->maxLength(100)
                            ->unique(Tag::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash'])
                            ->helperText(__('filament-communicate::default.forms.tag.fields.slug.helper_text'))
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament-communicate::default.forms.tag.fields.description.label'))
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('filament-communicate::default.forms.tag.sections.appearance'))
                    ->columns(3)
                    ->schema([
                        Forms\Components\ColorPicker::make('color')
                            ->label(__('filament-communicate::default.forms.tag.fields.color.label'))
                            ->default('#6366f1')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('icon')
                            ->label(__('filament-communicate::default.forms.tag.fields.icon.label'))
                            ->placeholder('heroicon-o-tag')
                            ->helperText(__('filament-communicate::default.forms.tag.fields.icon.helper_text'))
                            ->columnSpan(1),

                        Forms\Components\Select::make('rating')
                            ->label(__('filament-communicate::default.forms.tag.fields.rating.label'))
                            ->options([
                                1 => '1 - '.__('filament-communicate::default.forms.tag.fields.rating.options.very_low'),
                                2 => '2 - '.__('filament-communicate::default.forms.tag.fields.rating.options.low'),
                                3 => '3 - '.__('filament-communicate::default.forms.tag.fields.rating.options.below_average'),
                                4 => '4 - '.__('filament-communicate::default.forms.tag.fields.rating.options.below_normal'),
                                5 => '5 - '.__('filament-communicate::default.forms.tag.fields.rating.options.normal'),
                                6 => '6 - '.__('filament-communicate::default.forms.tag.fields.rating.options.above_normal'),
                                7 => '7 - '.__('filament-communicate::default.forms.tag.fields.rating.options.above_average'),
                                8 => '8 - '.__('filament-communicate::default.forms.tag.fields.rating.options.high'),
                                9 => '9 - '.__('filament-communicate::default.forms.tag.fields.rating.options.very_high'),
                                10 => '10 - '.__('filament-communicate::default.forms.tag.fields.rating.options.critical'),
                            ])
                            ->required()
                            ->default(5)
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make(__('filament-communicate::default.forms.tag.sections.settings'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament-communicate::default.forms.tag.fields.is_active.label'))
                            ->default(true)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('filament-communicate::default.forms.tag.fields.sort_order.label'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-communicate::default.tables.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (string $state, Tag $record): string {
                        $icon = $record->icon ? "<i class='{$record->icon} mr-2'></i>" : '';

                        return $icon.$state;
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament-communicate::default.tables.columns.slug'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('filament-communicate::default.tables.columns.description'))
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(),

                Tables\Columns\ColorColumn::make('color')
                    ->label(__('filament-communicate::default.tables.columns.color'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label(__('filament-communicate::default.tables.columns.rating'))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 9 => 'danger',
                        $state >= 7 => 'warning',
                        $state >= 5 => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament-communicate::default.tables.columns.is_active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('messages_count')
                    ->label(__('filament-communicate::default.tables.columns.messages_count'))
                    ->counts('messages')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('filament-communicate::default.tables.columns.sort_order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-communicate::default.tables.columns.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-communicate::default.tables.columns.updated_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament-communicate::default.tables.filters.is_active.label'))
                    ->placeholder(__('filament-communicate::default.tables.filters.is_active.placeholder'))
                    ->trueLabel(__('filament-communicate::default.tables.filters.is_active.true'))
                    ->falseLabel(__('filament-communicate::default.tables.filters.is_active.false')),

                Tables\Filters\SelectFilter::make('rating')
                    ->label(__('filament-communicate::default.tables.filters.rating.label'))
                    ->options([
                        1 => '1 - Muito Baixo',
                        2 => '2 - Baixo',
                        3 => '3 - Abaixo da Média',
                        4 => '4 - Abaixo do Normal',
                        5 => '5 - Normal',
                        6 => '6 - Acima do Normal',
                        7 => '7 - Acima da Média',
                        8 => '8 - Alto',
                        9 => '9 - Muito Alto',
                        10 => '10 - Crítico',
                    ]),

                Tables\Filters\Filter::make('high_priority')
                    ->label(__('filament-communicate::default.tables.filters.high_priority.label'))
                    ->query(fn (Builder $query): Builder => $query->where('rating', '>=', 8)),

                Tables\Filters\Filter::make('with_messages')
                    ->label(__('filament-communicate::default.tables.filters.with_messages.label'))
                    ->query(fn (Builder $query): Builder => $query->has('messages')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('filament-communicate::default.actions.activate.label'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn (Tag $record) => $record->update(['is_active' => true]));
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('filament-communicate::default.actions.deactivate.label'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn (Tag $record) => $record->update(['is_active' => false]));
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'view' => Pages\ViewTag::route('/{record}'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
