<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MessagePriority: string implements HasColor, HasIcon, HasLabel
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * Retorna o label em português para exibição
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::LOW => __('filament-communicate::default.enums.priority.low'),
            self::NORMAL => __('filament-communicate::default.enums.priority.normal'),
            self::HIGH => __('filament-communicate::default.enums.priority.high'),
            self::URGENT => __('filament-communicate::default.enums.priority.urgent'),
        };
    }

    /**
     * Retorna a cor para exibição no Filament
     */
    public function getColor(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::NORMAL => 'primary',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
        };
    }

    /**
     * Retorna o ícone para exibição
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::LOW => 'heroicon-o-arrow-down',
            self::NORMAL => 'heroicon-o-minus',
            self::HIGH => 'heroicon-o-arrow-up',
            self::URGENT => 'heroicon-o-exclamation-triangle',
        };
    }

    /**
     * Retorna o peso numérico para ordenação
     */
    public function weight(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    /**
     * Retorna todos os valores como array para select
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
