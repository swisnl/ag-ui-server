<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class RawEvent extends AgUiEvent
{
    public function __construct(
        /**
         * @var array<string, mixed>
         */
        public readonly array $event,
        public readonly ?string $source = null,
        ?\DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('Raw', $timestamp ?? new \DateTimeImmutable, $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        $data = ['event' => $this->event];

        if ($this->source !== null) {
            $data['source'] = $this->source;
        }

        return $data;
    }
}
