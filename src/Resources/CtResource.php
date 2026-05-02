<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\CtEvent;
use Kan\NkOpendata\DTO\Tile;
use Kan\NkOpendata\Exceptions\ApiException;
use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Hydrator\Hydrator;

class CtResource extends Resource {
    protected ?string $id = null;

    public function __construct(HttpClient $http, ?string $id = null)
    {
        $this->id = $id;
        return parent::__construct($http);
    }

    public function list(): array {
        return $this->map('ct', CtEvent::class);
    }

    public function recent(): CtEvent {
        return $this->list()[0];
    }

    public function current(): CtEvent {
        $event = $this->recent();

        if ($event->end->getTimestamp() < time()) {
            throw new ApiException('No currently active event');
        }

        return $this->list()[0];
    }

    public function find(string $id): self {
        return new self($this->http, $id);
    
        // return array_find($this->list(), function (CtEvent $event) use ($id) {
        //     return $event->id === $id;
        // });
    }

    public function tiles(): array
    {
        $this->requireId();

        return $this->transform(
            "ct/{$this->id}/tiles",
            fn($body) => Hydrator::hydrateCollection(Tile::class, $body['tiles'])
        );
    }

    public function leaderboard(): CtLeaderboardResource
    {
        $this->requireId();

        return new CtLeaderboardResource($this->http, $this->id);
    }

    private function requireId(): void
    {
        if (!$this->id) {
            throw new \LogicException('CT id required');
        }
    }
}