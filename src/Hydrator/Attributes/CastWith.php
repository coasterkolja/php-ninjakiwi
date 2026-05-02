<?php

namespace Kan\NkOpendata\Hydrator\Attributes;

use Kan\NkOpendata\Hydrator\Casts\Cast;

#[\Attribute]
class CastWith {
    public function __construct(public Cast $class) {}
}