<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Resources\MessageResource\Pages;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessagePermissions;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament-communicate::default.actions.create_message')),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $tabs = [];

        // Tab "Todas" apenas para super admins
        if (MessagePermissions::isSuperAdmin($user)) {
            $tabs['all'] = Tab::make(__('filament-communicate::default.tabs.all'))
                ->badge(fn () => $this->getModel()::count());
        }

        // Tabs padrão para todos os usuários
        $tabs['received'] = Tab::make(__('filament-communicate::default.tabs.received'))
            ->modifyQueryUsing(
                fn (Builder $query) => $query->where('current_recipient_id', auth()->id())
                    ->whereIn('status', [
                        MessageStatus::READ->value,
                        MessageStatus::APPROVED->value,
                        MessageStatus::SENT->value,
                    ])
            )
            ->badge(
                fn () => $this->getModel()::where('current_recipient_id', auth()->id())
                    ->whereIn('status', [
                        MessageStatus::READ->value,
                        MessageStatus::APPROVED->value,
                        MessageStatus::SENT->value,
                    ])
                    ->count()
            );

        $tabs['sent'] = Tab::make(__('filament-communicate::default.tabs.sent'))
            ->modifyQueryUsing(fn (Builder $query) => $query->where('sender_id', auth()->id()))
            ->badge(fn () => $this->getModel()::where('sender_id', auth()->id())->count());

        $tabs['unread'] = Tab::make(__('filament-communicate::default.tabs.unread'))
            ->modifyQueryUsing(
                fn (Builder $query) => $query->where('recipient_id', auth()->id())
                    ->whereNull('read_at')
                    ->whereIn('status', [
                        MessageStatus::APPROVED->value,
                        MessageStatus::SENT->value,
                    ])
            )
            ->badge(
                fn () => $this->getModel()::where('recipient_id', auth()->id())
                    ->whereNull('read_at')
                    ->whereIn('status', [
                        MessageStatus::APPROVED->value,
                        MessageStatus::SENT->value,
                    ])
                    ->count()
            );

        // Tab "Aguardando Aprovação" apenas para supervisores
        if (MessagePermissions::isSupervisor($user)) {
            $tabs['pending_approval'] = Tab::make(__('filament-communicate::default.tabs.pending_approval'))
                ->modifyQueryUsing(
                    fn (Builder $query) => $query->where('status', MessageStatus::PENDING)
                        ->where('sender_id', '!=', auth()->id())
                )
                ->badge(
                    fn () => $this->getModel()::where('status', MessageStatus::PENDING)
                        ->where('sender_id', '!=', auth()->id())
                        ->count()
                );
        }

        $tabs['drafts'] = Tab::make(__('filament-communicate::default.tabs.drafts'))
            ->modifyQueryUsing(fn (Builder $query) => $query->where('sender_id', auth()->id())->where('status', MessageStatus::DRAFT))
            ->badge(fn () => $this->getModel()::where('sender_id', auth()->id())->where('status', MessageStatus::DRAFT)->count());

        return $tabs;
    }
}
