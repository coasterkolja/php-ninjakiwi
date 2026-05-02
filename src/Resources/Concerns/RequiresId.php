<?php

namespace Kan\NkOpendata\Resources\Concerns;

trait RequiresId {
    protected ?string $id;
    
    protected function requireId(): void {
        if (!$this->id) {
            throw new \LogicException('Id required');
        }
    }
}