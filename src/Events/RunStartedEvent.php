<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class RunStartedEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $threadId,
        public readonly string $runId,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('RunStarted', $timestamp ?? new \DateTimeImmutable, $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'threadId' => $this->threadId,
            'runId' => $this->runId,
        ];
    }
}
