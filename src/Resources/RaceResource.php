<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\Race;
use Kan\NkOpendata\DTO\RaceLeaderboard;
use Kan\NkOpendata\DTO\RaceMetadata;
use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Resources\Concerns\RequiresId;

class RaceResource extends Resource {
    use RequiresId;
    
    public function __construct(HttpClient $http, ?string $id = null) {
        $this->id = $id;
        parent::__construct($http);
    }

    public function list(): array {
        return $this->map('races', Race::class);
    }

    public function leaderboard(): array {
        $this->requireId();

        return $this->map("races/{$this->id}/leaderboard", RaceLeaderboard::class);
    }

    public function metadata(): RaceMetadata {
        $this->requireId();

        return $this->get("races/{$this->id}/metadata", RaceMetadata::class);
    }
}