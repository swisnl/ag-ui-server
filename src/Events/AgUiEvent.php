<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

use Psr\EventDispatcher\StoppableEventInterface;

abstract class AgUiEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        public readonly string $type,
        public readonly \DateTimeImmutable $timestamp,
        /**
         * @var array<string, mixed>
         */
        public readonly array $rawEvent = []
    ) {
    }

    protected function toSnakeCase(string $input): string
    {
        $result = preg_replace('/(?<!^)[A-Z]/', '_$0', $input);

        return strtoupper($result ?? $input);
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->toSnakeCase($this->type),
            'timestamp' => $this->timestamp->format(\DateTimeInterface::RFC3339_EXTENDED),
            ...$this->getEventData(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function getEventData(): array;
}
