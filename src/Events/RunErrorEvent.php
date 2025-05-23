<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class RunErrorEvent extends AgUiEvent
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $code = null,
        \DateTimeImmutable $timestamp = null,
        /**
         * @var array<string, mixed>
         */
        array $rawEvent = []
    ) {
        parent::__construct('RunError', $timestamp ?? new \DateTimeImmutable(), $rawEvent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEventData(): array
    {
        $data = ['message' => $this->message];

        if ($this->code !== null) {
            $data['code'] = $this->code;
        }

        return $data;
    }
}
