<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Transporter;

use Swis\AgUiServer\Events\AgUiEvent;

class SseTransporter extends AbstractTransporter
{
    public function __construct(
        /**
         * @var array<string, string>
         */
        protected array $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ]
    ) {
    }

    public function initialize(): void
    {
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }

    /**
     * @throws \JsonException
     */
    protected function doSend(AgUiEvent $event): void
    {
        $data = $event->toJson();

        echo "data: {$data}\n\n";

        $this->flush();
    }

    public function sendComment(string $comment): void
    {
        echo ": {$comment}\n\n";

        $this->flush();
    }

    public function close(): void
    {
        // SSE doesn't require explicit closure
        $this->flush();
    }

    private function flush(): void
    {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
