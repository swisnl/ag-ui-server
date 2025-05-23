<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class TextMessageContentEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $delta,
        \DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('TextMessageContent', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'messageId' => $this->messageId,
            'delta' => $this->delta,
        ];
    }
}
