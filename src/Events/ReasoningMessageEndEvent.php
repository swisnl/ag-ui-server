<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Events;

class ReasoningMessageEndEvent extends TextMessageEndEvent
{
    protected string $eventName = 'ReasoningMessageEnd';
}
