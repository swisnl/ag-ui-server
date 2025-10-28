<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ToolCallResultEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $toolCallId,
        public readonly string $content,
        public readonly string $role,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('ToolCallResult', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'messageId' => $this->messageId,
            'toolCallId' => $this->toolCallId,
            'content' => $this->content,
            'role' => $this->role,
        ];
    }
}
