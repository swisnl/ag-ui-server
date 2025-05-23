<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class StepFinishedEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $stepName,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('StepFinished', $timestamp ?? new \DateTimeImmutable, $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'stepName' => $this->stepName,
        ];
    }
}
