<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class StateSnapshotEvent extends AgUiEvent
{
    /**
     * @var array<string, mixed>
     */
    public function __construct(
        public readonly array $snapshot,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('StateSnapshot', $timestamp ?? new \DateTimeImmutable, $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'snapshot' => $this->snapshot,
        ];
    }
}
