<?php

declare(strict_types=1);

namespace Swis\AgUiServer;

use Swis\AgUiServer\Events\RunErrorEvent;
use Swis\AgUiServer\Events\RunFinishedEvent;
use Swis\AgUiServer\Events\RunStartedEvent;
use Swis\AgUiServer\Events\StepFinishedEvent;
use Swis\AgUiServer\Events\StepStartedEvent;
use Swis\AgUiServer\Events\TextMessageContentEvent;
use Swis\AgUiServer\Events\TextMessageEndEvent;
use Swis\AgUiServer\Events\TextMessageStartEvent;
use Swis\AgUiServer\Events\ToolCallArgsEvent;
use Swis\AgUiServer\Events\ToolCallEndEvent;
use Swis\AgUiServer\Events\ToolCallStartEvent;
use Swis\AgUiServer\Transporter\SseTransporter;
use Swis\AgUiServer\Transporter\TransporterInterface;

/**
 * AgUiState manages the state of AI-generated UI interactions and events.
 *
 * This class provides a high-level interface for tracking runs, steps, messages,
 * and tool calls, coordinating their lifecycle events through a transporter.
 *
 * @phpstan-import-type Role from \Swis\AgUiServer\Events\TextMessageStartEvent
 */
class AgUiState
{
    /**
     * The transporter used to send events.
     *
     * @var TransporterInterface
     */
    protected TransporterInterface $transporter;

    /**
     * The ID of the currently active run.
     */
    protected ?string $currentRunId = null;

    /**
     * The ID of the current thread.
     */
    protected ?string $currentThreadId = null;

    /**
     * The name of the currently active step.
     */
    protected ?string $currentStepName = null;

    /**
     * Array of active message IDs mapped to their status.
     *
     * @var array<string, bool>
     */
    protected array $activeMessages = [];

    /**
     * Array of active tool call IDs mapped to their status.
     *
     * @var array<string, bool>
     */
    protected array $activeToolCalls = [];

    /**
     * Buffer for managing delta content updates.
     */
    protected ?DeltaBuffer $deltaBuffer = null;

    /**
     * Initialize the AgUiState with optional transporter.
     *
     * @param TransporterInterface|null $transporter The transporter for sending events (defaults to SseTransporter)
     */
    public function __construct(
        ?TransporterInterface $transporter = null
    ) {
        $this->transporter = $transporter ?? $this->defaultTransporter();
    }

    /**
     * Enable delta buffering with specified threshold and flush interval.
     *
     * @param int $deltaBufferThreshold Maximum number of deltas to buffer before flushing
     * @param float $deltaFlushInterval Time interval in seconds for automatic delta flushing
     * @return self Returns this instance for method chaining
     */
    public function withDeltaBuffering(int $deltaBufferThreshold = 100, float $deltaFlushInterval = 0.2): self
    {
        $this->deltaBuffer = new DeltaBuffer(
            $this->transporter,
            $deltaBufferThreshold,
            $deltaFlushInterval
        );

        return $this;
    }

    /**
     * Start a new run with the specified thread and run IDs.
     *
     * @param string $threadId The unique identifier for the thread
     * @param string $runId The unique identifier for the run
     */
    public function startRun(string $threadId, string $runId): void
    {
        $this->currentThreadId = $threadId;
        $this->currentRunId = $runId;

        $this->transporter->send(new RunStartedEvent($threadId, $runId));
    }

    /**
     * Finish the current run and clear the active run state.
     */
    public function finishRun(): void
    {
        if ($this->currentRunId && $this->currentThreadId) {
            $this->transporter->send(new RunFinishedEvent($this->currentThreadId, $this->currentRunId));
        }

        $this->currentRunId = null;
        $this->currentThreadId = null;
    }

    /**
     * Mark the current run as errored and clear the active run state.
     *
     * @param string $message The error message
     * @param string|null $code Optional error code
     */
    public function errorRun(string $message, ?string $code = null): void
    {
        $this->transporter->send(new RunErrorEvent($message, $code));
        $this->currentRunId = null;
        $this->currentThreadId = null;
    }

    /**
     * Start a new step within the current run.
     *
     * @param string $stepName The name of the step to start
     */
    public function startStep(string $stepName): void
    {
        $this->currentStepName = $stepName;
        $this->transporter->send(new StepStartedEvent($stepName));
    }

    /**
     * Finish the current step and clear the active step state.
     */
    public function finishStep(): void
    {
        if ($this->currentStepName) {
            $this->transporter->send(new StepFinishedEvent($this->currentStepName));
            $this->currentStepName = null;
        }
    }

    /**
     * Add a complete message with the specified content and role.
     *
     * @param string|\Closure|iterable<string> $content The message content (string, closure returning content, or iterable for streaming)
     * @param Role $role The role of the message sender (default: 'assistant')
     * @param string|null $id Optional message ID (auto-generated if not provided)
     * @return string The message ID
     */
    public function addMessage(string|\Closure|iterable $content, string $role = 'assistant', ?string $id = null): string
    {
        $messageId = $id ?? 'msg_' . uniqid();

        if ($content instanceof \Closure) {
            $content = $content();
        }

        if (is_string($content)) {
            $this->sendCompleteMessage($messageId, $content, $role);
        } elseif (is_iterable($content)) {
            $this->streamMessageContent($messageId, $content, $role);
        }

        return $messageId;
    }

    /**
     * Start a new message that will be built incrementally.
     *
     * @param Role $role The role of the message sender (default: 'assistant')
     * @param string|null $id Optional message ID (auto-generated if not provided)
     * @return string The message ID
     */
    public function startMessage(string $role = 'assistant', ?string $id = null): string
    {
        $messageId = $id ?? 'msg_' . uniqid();
        $this->activeMessages[$messageId] = true;

        $this->transporter->send(new TextMessageStartEvent($messageId, $role));

        return $messageId;
    }

    /**
     * Add content delta to an active message.
     *
     * @param string $delta The content delta to add
     * @param string|null $messageId The message ID (uses most recent active message if not provided)
     */
    public function addMessageContent(string $delta, ?string $messageId = null): void
    {
        $messageId = $messageId ?? $this->getMostRecentActiveMessage();

        if ($messageId === null) {
            throw new \InvalidArgumentException('No active message found and no message ID provided');
        }

        if ($this->deltaBuffer) {
            $this->deltaBuffer->add($messageId, $delta);
        } else {
            $this->transporter->send(new TextMessageContentEvent($messageId, $delta));
        }
    }

    /**
     * Finish an active message and remove it from the active messages list.
     *
     * @param string|null $messageId The message ID (uses most recent active message if not provided)
     */
    public function finishMessage(?string $messageId = null): void
    {
        $messageId = $messageId ?? $this->getMostRecentActiveMessage();

        if ($messageId === null) {
            throw new \InvalidArgumentException('No active message found and no message ID provided');
        }

        if ($this->deltaBuffer) {
            $this->deltaBuffer->flush($messageId);
        }

        $this->transporter->send(new TextMessageEndEvent($messageId));
        unset($this->activeMessages[$messageId]);
    }

    /**
     * Send a complete message in a single operation.
     *
     * @param string $messageId The message ID
     * @param string $content The complete message content
     * @param Role $role The role of the message sender
     */
    private function sendCompleteMessage(string $messageId, string $content, string $role): void
    {
        $this->transporter->send(new TextMessageStartEvent($messageId, $role));
        $this->transporter->send(new TextMessageContentEvent($messageId, $content));
        $this->transporter->send(new TextMessageEndEvent($messageId));
    }

    /**
     * Stream message content from an iterable source.
     *
     * @param string $messageId The message ID
     * @param iterable<string> $content The iterable content source
     * @param Role $role The role of the message sender
     */
    private function streamMessageContent(string $messageId, iterable $content, string $role): void
    {
        $this->transporter->send(new TextMessageStartEvent($messageId, $role));

        foreach ($content as $delta) {
            if ($this->deltaBuffer) {
                $this->deltaBuffer->add($messageId, (string) $delta);
            } else {
                $this->transporter->send(new TextMessageContentEvent($messageId, (string) $delta));
            }
        }

        if ($this->deltaBuffer) {
            $this->deltaBuffer->flush($messageId);
        }
        $this->transporter->send(new TextMessageEndEvent($messageId));
    }

    /**
     * Add a complete tool call with the specified name and arguments.
     *
     * @param string $toolName The name of the tool being called
     * @param string|\Closure|iterable<string> $args The tool arguments (string, closure returning args, or iterable for streaming)
     * @param string|null $id Optional tool call ID (auto-generated if not provided)
     * @param string|null $parentMessageId The ID of the parent message (uses most recent active message if not provided)
     * @return string The tool call ID
     */
    public function addToolCall(string $toolName, string|\Closure|iterable $args, ?string $id = null, ?string $parentMessageId = null): string
    {
        $toolCallId = $id ?? 'tool_' . uniqid();
        $parentMessageId = $parentMessageId ?? $this->getMostRecentActiveMessage();

        if ($args instanceof \Closure) {
            $args = $args();
        }

        $this->transporter->send(new ToolCallStartEvent($toolCallId, $toolName, $parentMessageId));

        if (is_string($args)) {
            $this->transporter->send(new ToolCallArgsEvent($toolCallId, $args));
        } elseif (is_iterable($args)) {
            foreach ($args as $argsDelta) {
                $this->transporter->send(new ToolCallArgsEvent($toolCallId, (string) $argsDelta));
            }
        }

        $this->transporter->send(new ToolCallEndEvent($toolCallId));

        return $toolCallId;
    }

    /**
     * Start a new tool call that will be built incrementally.
     *
     * @param string $toolName The name of the tool being called
     * @param string|null $parentMessageId The ID of the parent message
     * @param string|null $id Optional tool call ID (auto-generated if not provided)
     * @return string The tool call ID
     */
    public function startToolCall(string $toolName, ?string $parentMessageId = null, ?string $id = null): string
    {
        $toolCallId = $id ?? 'tool_' . uniqid();
        $this->activeToolCalls[$toolCallId] = true;

        $this->transporter->send(new ToolCallStartEvent($toolCallId, $toolName, $parentMessageId));

        return $toolCallId;
    }

    /**
     * Add arguments to an active tool call.
     *
     * @param string $args The arguments to add
     * @param string|null $toolCallId The tool call ID (uses most recent active tool call if not provided)
     */
    public function addToolCallArgs(string $args, ?string $toolCallId = null): void
    {
        $toolCallId = $toolCallId ?? $this->getMostRecentActiveToolCall();

        if ($toolCallId === null) {
            throw new \InvalidArgumentException('No active tool call found and no tool call ID provided');
        }

        $this->transporter->send(new ToolCallArgsEvent($toolCallId, $args));
    }

    /**
     * Finish an active tool call and remove it from the active tool calls list.
     *
     * @param string|null $toolCallId The tool call ID (uses most recent active tool call if not provided)
     */
    public function finishToolCall(?string $toolCallId = null): void
    {
        $toolCallId = $toolCallId ?? $this->getMostRecentActiveToolCall();

        if ($toolCallId === null) {
            throw new \InvalidArgumentException('No active tool call found and no tool call ID provided');
        }

        $this->transporter->send(new ToolCallEndEvent($toolCallId));
        unset($this->activeToolCalls[$toolCallId]);
    }

    /**
     * Get the ID of the most recently started active message.
     *
     * @return string|null The message ID or null if no active messages
     */
    private function getMostRecentActiveMessage(): ?string
    {
        return array_key_last($this->activeMessages);
    }

    /**
     * Get the ID of the most recently started active tool call.
     *
     * @return string|null The tool call ID or null if no active tool calls
     */
    private function getMostRecentActiveToolCall(): ?string
    {
        return array_key_last($this->activeToolCalls);
    }

    /**
     * Create and initialize the default SSE transporter.
     *
     * @return SseTransporter The initialized transporter
     */
    protected function defaultTransporter(): SseTransporter
    {
        $transporter = new SseTransporter();
        $transporter->initialize();

        return $transporter;
    }
}
