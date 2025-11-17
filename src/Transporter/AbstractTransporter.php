<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Transporter;

use Psr\EventDispatcher\EventDispatcherInterface;
use Swis\AgUiServer\Events\AgUiEvent;
use Swis\AgUiServer\Events\CustomEvent;
use Swis\AgUiServer\Events\MessagesSnapshotEvent;
use Swis\AgUiServer\Events\RawEvent;
use Swis\AgUiServer\Events\ReasoningEndEvent;
use Swis\AgUiServer\Events\ReasoningMessageContentEvent;
use Swis\AgUiServer\Events\ReasoningMessageEndEvent;
use Swis\AgUiServer\Events\ReasoningMessageStartEvent;
use Swis\AgUiServer\Events\ReasoningStartEvent;
use Swis\AgUiServer\Events\RunErrorEvent;
use Swis\AgUiServer\Events\RunFinishedEvent;
use Swis\AgUiServer\Events\RunStartedEvent;
use Swis\AgUiServer\Events\StateDeltaEvent;
use Swis\AgUiServer\Events\StateSnapshotEvent;
use Swis\AgUiServer\Events\StepFinishedEvent;
use Swis\AgUiServer\Events\StepStartedEvent;
use Swis\AgUiServer\Events\TextMessageContentEvent;
use Swis\AgUiServer\Events\TextMessageEndEvent;
use Swis\AgUiServer\Events\TextMessageStartEvent;
use Swis\AgUiServer\Events\ToolCallArgsEvent;
use Swis\AgUiServer\Events\ToolCallEndEvent;
use Swis\AgUiServer\Events\ToolCallStartEvent;

abstract class AbstractTransporter implements TransporterInterface
{
    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function sendEvent(AgUiEvent $event): void
    {
        $this->doSend($event);
    }

    /**
     * Template method for actual sending implementation
     */
    abstract protected function doSend(AgUiEvent $event): void;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher, ?\Closure $registerListenerCallback = null): void
    {
        $this->eventDispatcher = $eventDispatcher;

        $this->registerEventListeners(CustomEvent::class, $registerListenerCallback);
        $this->registerEventListeners(MessagesSnapshotEvent::class, $registerListenerCallback);
        $this->registerEventListeners(RawEvent::class, $registerListenerCallback);
        $this->registerEventListeners(RunErrorEvent::class, $registerListenerCallback);
        $this->registerEventListeners(RunFinishedEvent::class, $registerListenerCallback);
        $this->registerEventListeners(RunStartedEvent::class, $registerListenerCallback);
        $this->registerEventListeners(StateDeltaEvent::class, $registerListenerCallback);
        $this->registerEventListeners(StateSnapshotEvent::class, $registerListenerCallback);
        $this->registerEventListeners(StepFinishedEvent::class, $registerListenerCallback);
        $this->registerEventListeners(StepStartedEvent::class, $registerListenerCallback);
        $this->registerEventListeners(TextMessageContentEvent::class, $registerListenerCallback);
        $this->registerEventListeners(TextMessageEndEvent::class, $registerListenerCallback);
        $this->registerEventListeners(TextMessageStartEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ReasoningMessageContentEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ReasoningMessageEndEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ReasoningMessageStartEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ReasoningEndEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ReasoningStartEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ToolCallArgsEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ToolCallEndEvent::class, $registerListenerCallback);
        $this->registerEventListeners(ToolCallStartEvent::class, $registerListenerCallback);
    }

    /**
     * Register this transporter as a listener for events
     */
    private function registerEventListeners(string $eventIdentifier, ?\Closure $registerListenerCallback = null): void
    {
        $listenerCallback = [$this, 'handleEvent'];

        if ($registerListenerCallback) {
            $registerListenerCallback(
                $eventIdentifier,
                $listenerCallback,
                $this->eventDispatcher,
            );

            return;
        }

        // Implementation depends on your PSR-14 dispatcher
        // Actual implementation varies by dispatcher,
        // lets try some common ones

        if ($this->eventDispatcher === null) {
            throw new \RuntimeException('Event dispatcher is not set.');
        }

        match (true) {
            method_exists($this->eventDispatcher, 'addListener') => $this->eventDispatcher->addListener($eventIdentifier, $listenerCallback),
            method_exists($this->eventDispatcher, 'listen') => $this->eventDispatcher->listen($eventIdentifier, $listenerCallback),
            method_exists($this->eventDispatcher, 'subscribeTo') => $this->eventDispatcher->subscribeTo($eventIdentifier, $listenerCallback),
            method_exists($this->eventDispatcher, 'attach') => $this->eventDispatcher->attach($eventIdentifier, $listenerCallback),
            default => throw new \RuntimeException('Unsupported event dispatcher. Provide a registerListenerCallback closure.'),
        };
    }

    /**
     * Handle incoming events from the event dispatcher
     */
    public function handleEvent(AgUiEvent $event): void
    {
        $this->sendEvent($event);
    }
}
