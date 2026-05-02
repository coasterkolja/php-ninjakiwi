<?php

namespace Kan\NkOpendata\Enums;

enum TileGameType: string {
    case LeastCash = 'LeastCash';
    case LeastTiers = 'LeastTiers';
    case Boss = 'Boss';
    case Race = 'Race';
    case TeamStart = 'TeamStart';

    public static function fromString(string $value): self {
        return match ($value) {
            'LeastCash' => self::LeastCash,
            'LeastTiers' => self::LeastTiers,
            'Boss' => self::Boss,
            'Race' => self::Race,
            'TeamStart' => self::TeamStart,
            default => throw new \InvalidArgumentException("Unknown tile game type: {$value}"),
        };
    }
}