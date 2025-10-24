<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ReasoningEndEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $messageId,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('ReasoningEnd', $timestamp ?? new \DateTimeImmutable, $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'messageId' => $this->messageId,
        ];
    }
}
