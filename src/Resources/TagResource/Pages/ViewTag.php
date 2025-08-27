<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\TagResource\Pages;

use Alessandronuunes\FilamentCommunicate\Resources\TagResource;
use Filament\Actions;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTag extends ViewRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('filament-communicate::default.actions.edit.label')),
            Actions\DeleteAction::make()
                ->label(__('filament-communicate::default.actions.delete.label'))
                ->before(function ($record) {
                    // Verificar se a tag está sendo usada antes de deletar
                    if ($record->isInUse()) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('filament-communicate::default.notifications.tag.cannot_delete.title'))
                            ->body(__('filament-communicate::default.notifications.tag.cannot_delete.body'))
                            ->danger()
                            ->send();

                        return false; // Cancela a deleção
                    }
                }),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-communicate::default.pages.tags.view.title');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('filament-communicate::default.sections.tag_details.title'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('filament-communicate::default.fields.name.label'))
                                    ->weight('bold'),
                                TextEntry::make('slug')
                                    ->label(__('filament-communicate::default.fields.slug.label'))
                                    ->copyable(),
                            ]),
                        TextEntry::make('description')
                            ->label(__('filament-communicate::default.fields.description.label'))
                            ->columnSpanFull(),
                        Grid::make(3)
                            ->schema([
                                ColorEntry::make('color')
                                    ->label(__('filament-communicate::default.fields.color.label')),
                                TextEntry::make('icon')
                                    ->label(__('filament-communicate::default.fields.icon.label'))
                                    ->icon(fn ($state) => $state),
                                TextEntry::make('rating')
                                    ->label(__('filament-communicate::default.fields.rating.label'))
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        $state >= 8 => 'danger',
                                        $state >= 6 => 'warning',
                                        $state >= 4 => 'info',
                                        default => 'success',
                                    }),
                            ]),
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('is_active')
                                    ->label(__('filament-communicate::default.fields.is_active.label'))
                                    ->boolean(),
                                TextEntry::make('sort_order')
                                    ->label(__('filament-communicate::default.fields.sort_order.label')),
                                TextEntry::make('messages_count')
                                    ->label(__('filament-communicate::default.fields.messages_count.label'))
                                    ->state(fn ($record) => $record->messages()->count()),
                            ]),
                    ]),
                Section::make(__('filament-communicate::default.sections.timestamps.title'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('filament-communicate::default.fields.created_at.label'))
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label(__('filament-communicate::default.fields.updated_at.label'))
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
