<?php

namespace Kan\NkOpendata\Hydrator\Casts;

interface Cast {
    public function cast(mixed $value): mixed;
}