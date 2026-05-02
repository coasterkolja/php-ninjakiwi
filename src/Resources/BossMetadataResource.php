<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\BossMetadata;
use Kan\NkOpendata\Http\HttpClient;

class BossMetadataResource extends Resource {
    protected string $id;

    public function __construct(HttpClient $http, string $id) {
        $this->id = $id;
        parent::__construct($http);
    }

    public function standard(): BossMetadata {
        return $this->get("bosses/{$this->id}/metadata/standard", BossMetadata::class);
    }

    public function elite(): BossMetadata {
        return $this->get("bosses/{$this->id}/metadata/elite", BossMetadata::class);
    }
}