<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ToolCallStartEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $toolCallId,
        public readonly string $toolCallName,
        public readonly ?string $parentMessageId = null,
        \DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('ToolCallStart', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        $data = [
            'toolCallId' => $this->toolCallId,
            'toolCallName' => $this->toolCallName,
        ];

        if ($this->parentMessageId !== null) {
            $data['parentMessageId'] = $this->parentMessageId;
        }

        return $data;
    }
}
