<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TagRating: int implements HasColor, HasLabel
{
    case VERY_LOW = 1;
    case LOW = 2;
    case BELOW_AVERAGE = 3;
    case BELOW_NORMAL = 4;
    case NORMAL = 5;
    case ABOVE_NORMAL = 6;
    case ABOVE_AVERAGE = 7;
    case HIGH = 8;
    case VERY_HIGH = 9;
    case CRITICAL = 10;

    /**
     * Retorna o label traduzido para o rating
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::VERY_LOW => __('filament-communicate::default.enums.tag_rating.very_low'),
            self::LOW => __('filament-communicate::default.enums.tag_rating.low'),
            self::BELOW_AVERAGE => __('filament-communicate::default.enums.tag_rating.below_average'),
            self::BELOW_NORMAL => __('filament-communicate::default.enums.tag_rating.below_normal'),
            self::NORMAL => __('filament-communicate::default.enums.tag_rating.normal'),
            self::ABOVE_NORMAL => __('filament-communicate::default.enums.tag_rating.above_normal'),
            self::ABOVE_AVERAGE => __('filament-communicate::default.enums.tag_rating.above_average'),
            self::HIGH => __('filament-communicate::default.enums.tag_rating.high'),
            self::VERY_HIGH => __('filament-communicate::default.enums.tag_rating.very_high'),
            self::CRITICAL => __('filament-communicate::default.enums.tag_rating.critical'),
        };
    }

    /**
     * Retorna o label com o número para exibição no select
     */
    public function getSelectLabel(): string
    {
        return $this->value.' - '.$this->getLabel();
    }

    /**
     * Retorna todas as opções para uso em selects
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getSelectLabel();
        }

        return $options;
    }

    /**
     * Retorna a cor baseada no rating para badges
     */
    public function getColor(): string
    {
        return match (true) {
            $this->value >= 9 => 'danger',
            $this->value >= 7 => 'warning',
            $this->value >= 5 => 'success',
            default => 'gray',
        };
    }
}
