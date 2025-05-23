<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

/**
 * @phpstan-type Role 'developer'|'system'|'assistant'|'user'|'tool'
 */
class TextMessageStartEvent extends AgUiEvent
{
    /**
     * @param Role $role
     */
    public function __construct(
        public readonly string $messageId,
        public readonly string $role = 'assistant',
        \DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('TextMessageStart', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'messageId' => $this->messageId,
            'role' => $this->role,
        ];
    }
}
