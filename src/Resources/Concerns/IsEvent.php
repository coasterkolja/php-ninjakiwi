<?php

namespace Kan\NkOpendata\Resources\Concerns;

use Kan\NkOpendata\Exceptions\ApiException;

trait IsEvent
{
  protected ?string $id;

  /**
   * @return T
   */
  public function recent()
  {
    $event = $this->list()[0];
    $this->id = $event->id;
    return $event;
  }

  /**
   * @throws ApiException
   * @return T
   */
  public function current()
  {
    $events = $this->list();
    $event = $events[0];

    if ($event->end->getTimestamp() < time()) {
      throw new ApiException("No currently active Event");
    }

    return $event;
  }
}
