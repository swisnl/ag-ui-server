<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ToolCallArgsEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $toolCallId,
        public readonly string $delta,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('ToolCallArgs', $timestamp ?? new \DateTimeImmutable, $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'toolCallId' => $this->toolCallId,
            'delta' => $this->delta,
        ];
    }
}
