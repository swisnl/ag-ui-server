<?php

declare(strict_types=1);

namespace Swis\AgUiServer;

use Swis\AgUiServer\Events\TextMessageContentEvent;
use Swis\AgUiServer\Transporter\TransporterInterface;

class DeltaBuffer
{
    /**
     * @var array<string, string>
     */
    private array $buffers = [];

    /**
     * @var array<string, float>
     */
    private array $lastFlush = [];

    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly int $bufferThreshold = 100,
        private readonly float $flushInterval = 0.2
    ) {
    }

    public function add(string $messageId, string $delta): void
    {
        if (! isset($this->buffers[$messageId])) {
            $this->buffers[$messageId] = '';
            $this->lastFlush[$messageId] = microtime(true);
        }

        $this->buffers[$messageId] .= $delta;

        if ($this->shouldFlush($messageId)) {
            $this->flush($messageId);
        }
    }

    public function flush(string $messageId): void
    {
        if (isset($this->buffers[$messageId]) && $this->buffers[$messageId] !== '') {
            $this->transporter->sendEvent(new TextMessageContentEvent($messageId, $this->buffers[$messageId]));
            $this->buffers[$messageId] = '';
            $this->lastFlush[$messageId] = microtime(true);
        }
    }

    public function flushAll(): void
    {
        foreach (array_keys($this->buffers) as $messageId) {
            $this->flush($messageId);
        }
    }

    private function shouldFlush(string $messageId): bool
    {
        $bufferSize = strlen($this->buffers[$messageId]);
        $timeSinceLastFlush = microtime(true) - $this->lastFlush[$messageId];

        return $bufferSize >= $this->bufferThreshold || $timeSinceLastFlush >= $this->flushInterval;
    }
}
