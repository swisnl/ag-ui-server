<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class StateDeltaEvent extends AgUiEvent
{
    /**
     * @var array<string, mixed>
     */
    public function __construct(
        public readonly array $delta,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('StateDelta', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'delta' => $this->delta,
        ];
    }
}
