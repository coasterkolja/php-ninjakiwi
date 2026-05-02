<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\User;

class UserResource extends Resource {
    public function find(string $id): User {
        return $this->get("users/{$id}", User::class);
    }
}