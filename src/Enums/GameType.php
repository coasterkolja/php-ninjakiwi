<?php

namespace Kan\NkOpendata\Enums;

enum GameType: string
{
  case LeastCash = 'LeastCash';
  case LeastTiers = 'LeastTiers';
  case Boss = 'Boss';
  case Race = 'Race';
  case TeamStart = 'TeamStart';
  case GameTime = 'GameTime';

  public static function fromString(string $value): self
  {
    return match ($value) {
      'LeastCash' => self::LeastCash,
      'LeastTiers' => self::LeastTiers,
      'Boss' => self::Boss,
      'Race' => self::Race,
      'TeamStart' => self::TeamStart,
      'GameTime' => self::GameTime,
      default => throw new \InvalidArgumentException("Unknown tile game type: {$value}"),
    };
  }
}
