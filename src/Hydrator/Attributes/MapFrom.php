<?php

namespace Kan\NkOpendata\Hydrator\Attributes;

#[\Attribute]
class MapFrom {
    public function __construct(public string $field) {}
}