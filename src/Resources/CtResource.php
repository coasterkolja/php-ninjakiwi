<?php

namespace Kan\NkOpendata\Resources;

use Kan\NkOpendata\DTO\CtEvent;
use Kan\NkOpendata\DTO\Tile;
use Kan\NkOpendata\Exceptions\ApiException;
use Kan\NkOpendata\Http\HttpClient;
use Kan\NkOpendata\Hydrator\Hydrator;
use Kan\NkOpendata\Resources\Concerns\RequiresId;

class CtResource extends Resource {
    use RequiresId;

    public function __construct(HttpClient $http, ?string $id = null)
    {
        $this->id = $id;
        parent::__construct($http);
    }

    /** @return array<int, CtEvent> */
    public function list(): array {
        return $this->map('ct', CtEvent::class);
    }

    public function recent(): CtEvent {
        return $this->list()[0];
    }

    public function current(): CtEvent {
        $events = $this->list();
        $event = $events[0];

        if ($event->end->getTimestamp() < time()) {
            throw new ApiException('No currently active event');
        }

        return $event;
    }

    public function find(string $id): self {
        return new self($this->http, $id);
    }

    /** @return array<int, Tile> */
    public function tiles(): array
    {
        $this->requireId();

        return $this->transform(
            "ct/{$this->id}/tiles",
            function (array $body): array {
                /** @var array<int, array<string, mixed>> $tiles */
                $tiles = $body['tiles'];

                return Hydrator::hydrateCollection(Tile::class, $tiles);
            }
        );
    }

    public function leaderboard(): CtLeaderboardResource
    {
        $this->requireId();

        /** @var string $id */
        $id = $this->id;

        return new CtLeaderboardResource($this->http, $id);
    }
}