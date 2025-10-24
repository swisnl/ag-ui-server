<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ReasoningMessageContentEvent extends TextMessageContentEvent
{
    protected string $eventName = 'ReasoningMessageContent';
}
