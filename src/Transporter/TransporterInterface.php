<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Transporter;

use Swis\AgUiServer\Events\AgUiEvent;

interface TransporterInterface
{
    /**
     * Initialize the transporter (e.g., send headers, establish connection)
     */
    public function initialize(): void;

    /**
     * Send an AG-UI event
     */
    public function send(AgUiEvent $event): void;

    /**
     * Send a comment (for keep-alive, debugging, etc.)
     */
    public function sendComment(string $comment): void;

    /**
     * Close/cleanup the transporter
     */
    public function close(): void;
}
