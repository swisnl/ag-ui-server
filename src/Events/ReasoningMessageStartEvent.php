<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ReasoningMessageStartEvent extends TextMessageStartEvent
{
    protected string $eventName = 'ReasoningMessageStart';
}
