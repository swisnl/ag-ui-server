<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ToolCallEndEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $toolCallId,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('ToolCallEnd', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'toolCallId' => $this->toolCallId,
        ];
    }
}
