<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class CustomEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $value,
        \DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('Custom', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }
}
