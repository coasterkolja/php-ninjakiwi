<?php

namespace Kan\NkOpendata\DTO;

class Leadeboard implements DTOInterface {
    public static function fromArray(array $data): self {
        return new self();
    }
}