<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

/**
 * @phpstan-type BaseMessage array{
 *     id: string,
 *     role: string,
 *     content?: string,
 *     name?: string
 * }
 * @phpstan-type DeveloperMessage array{
 *     id: string,
 *     role: 'developer',
 *     content: string,
 *     name?: string
 * }
 * @phpstan-type SystemMessage array{
 *     id: string,
 *     role: 'system',
 *     content: string,
 *     name?: string
 * }
 * @phpstan-type ToolCall array{
 *     id: string,
 *     type: string,
 *     function: array{name: string, arguments?: string}
 * }
 * @phpstan-type AssistantMessage array{
 *     id: string,
 *     role: 'assistant',
 *     content?: string,
 *     name?: string,
 *     toolCalls?: list<ToolCall>
 * }
 * @phpstan-type UserMessage array{
 *     id: string,
 *     role: 'user',
 *     content: string,
 *     name?: string
 * }
 * @phpstan-type ToolMessage array{
 *     id: string,
 *     content: string,
 *     role: 'tool',
 *     toolCallId: string
 * }
 * @phpstan-type Message DeveloperMessage|SystemMessage|AssistantMessage|UserMessage|ToolMessage
 */
class MessagesSnapshotEvent extends AgUiEvent
{
    /**
     * @param  list<Message>  $messages
     */
    public function __construct(
        public readonly array $messages,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('MessagesSnapshot', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'messages' => $this->messages,
        ];
    }
}
