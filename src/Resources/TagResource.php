<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use TomatoPHP\FilamentIcons\Components\IconPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Alessandronuunes\FilamentCommunicate\Models\Tag;
use Awcodes\Palette\Forms\Components\ColorPickerSelect;
use Alessandronuunes\FilamentCommunicate\Enums\TagRating;
use Alessandronuunes\FilamentCommunicate\Resources\TagResource\Pages;

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
                        ColorPickerSelect::make('color')
                                        ->label(__('filament-communicate::default.forms.message.fields.tags.color.label'))
                                        ->colors(fn () => Color::all())
                                        ->columnSpanFull()
                                        ->storeAsKey(),
                                    IconPicker::make('icon')
                                        ->columnSpanFull()
                                        ->label(__('filament-communicate::default.forms.message.fields.tags.icon.label'))
                                        ->hintAction(
                                            Forms\Components\Actions\Action::make('viewIcon')
                                                ->label(__('filament-communicate::default.forms.message.fields.tags.icon.hint'))
                                                ->link()
                                                ->url('https://heroicons.com/', true)
                                        )
                                        ->default('heroicon-o-academic-cap')
                                        ->required(),

                        Forms\Components\Select::make('rating')
                            ->label(__('filament-communicate::default.forms.tag.fields.rating.label'))
                            ->options(TagRating::getSelectOptions())
                            ->required()
                            ->default(TagRating::NORMAL->value)
                            ->columnSpanFull(),
                        
                        Forms\Components\Checkbox::make('is_active')
                            ->label(__('filament-communicate::default.forms.tag.fields.is_active.label'))
                            ->default(true)
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament-communicate::default.tables.columns.is_active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->icon(fn (Tag $record) => $record->icon)
                    ->color(fn (Tag $record) => Arr::get(Color::all(), $record->color))
                    ->searchable()
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament-communicate::default.tables.columns.slug'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('rating')
                    ->label(__('filament-communicate::default.tables.columns.rating'))
                    ->badge()
                    ->sortable(),


                Tables\Columns\TextColumn::make('description')
                    ->label(__('filament-communicate::default.tables.columns.description'))
                    ->limit(50)
                    ->toggleable(),
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
                    ->options(TagRating::getSelectOptions()),

                Tables\Filters\Filter::make('high_priority')
                    ->label(__('filament-communicate::default.tables.filters.high_priority.label'))
                    ->query(fn (Builder $query): Builder => $query->where('rating', '>=', 8)),

                Tables\Filters\Filter::make('with_messages')
                    ->label(__('filament-communicate::default.tables.filters.with_messages.label'))
                    ->query(fn (Builder $query): Builder => $query->has('messages')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([

                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
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
        ];
    }
}
