<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\AgUiServer\AgUiState;
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
use Swis\AgUiServer\Transporter\TransporterInterface;

class AgUiStateSimpleTest extends TestCase
{
    private array $sentEvents = [];

    private TransporterInterface $mockTransporter;

    private AgUiState $state;

    protected function setUp(): void
    {
        $this->sentEvents = [];
        $this->mockTransporter = $this->createMock(TransporterInterface::class);
        $this->mockTransporter->method('initialize');
        $this->mockTransporter->method('sendEvent')
            ->willReturnCallback(function ($event) {
                $this->sentEvents[] = $event;
            });

        $this->state = new AgUiState($this->mockTransporter);
    }

    public function test_start_and_finish_run(): void
    {
        $threadId = 'thread_123';
        $runId = 'run_456';

        $this->state->startRun($threadId, $runId);
        $this->state->finishRun();

        $this->assertCount(2, $this->sentEvents);
        $this->assertInstanceOf(RunStartedEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(RunFinishedEvent::class, $this->sentEvents[1]);

        $this->assertSame($threadId, $this->sentEvents[0]->threadId);
        $this->assertSame($runId, $this->sentEvents[0]->runId);
    }

    public function test_error_run(): void
    {
        $threadId = 'thread_123';
        $runId = 'run_456';
        $errorMessage = 'Something went wrong';
        $errorCode = 'ERR_001';

        $this->state->startRun($threadId, $runId);
        $this->state->errorRun($errorMessage, $errorCode);

        $this->assertCount(2, $this->sentEvents);
        $this->assertInstanceOf(RunStartedEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(RunErrorEvent::class, $this->sentEvents[1]);

        $errorEvent = $this->sentEvents[1];
        $this->assertSame($errorMessage, $errorEvent->message);
        $this->assertSame($errorCode, $errorEvent->code);
    }

    public function test_step_lifecycle(): void
    {
        $stepName = 'processing';

        $this->state->startStep($stepName);
        $this->state->finishStep();

        $this->assertCount(2, $this->sentEvents);
        $this->assertInstanceOf(StepStartedEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(StepFinishedEvent::class, $this->sentEvents[1]);

        $this->assertSame($stepName, $this->sentEvents[0]->stepName);
        $this->assertSame($stepName, $this->sentEvents[1]->stepName);
    }

    public function test_add_message_with_string(): void
    {
        $content = 'Hello world';
        $role = 'developer';

        $messageId = $this->state->addMessage($content, $role);

        $this->assertStringStartsWith('msg_', $messageId);
        $this->assertCount(3, $this->sentEvents);

        $this->assertInstanceOf(TextMessageStartEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(TextMessageContentEvent::class, $this->sentEvents[1]);
        $this->assertInstanceOf(TextMessageEndEvent::class, $this->sentEvents[2]);

        $this->assertSame($messageId, $this->sentEvents[0]->messageId);
        $this->assertSame($role, $this->sentEvents[0]->role);
        $this->assertSame($content, $this->sentEvents[1]->delta);
    }

    public function test_add_message_with_closure(): void
    {
        $content = 'Generated content';

        $messageId = $this->state->addMessage(function () use ($content) {
            return $content;
        });

        $this->assertCount(3, $this->sentEvents);
        $this->assertSame($content, $this->sentEvents[1]->delta);
        $this->assertSame('assistant', $this->sentEvents[0]->role);
    }

    public function test_manual_message_control(): void
    {
        $role = 'user';
        $delta = 'Test content';

        $messageId = $this->state->startMessage($role);
        $this->state->addMessageContent($delta, $messageId);
        $this->state->finishMessage($messageId);

        $this->assertCount(3, $this->sentEvents);
        $this->assertInstanceOf(TextMessageStartEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(TextMessageContentEvent::class, $this->sentEvents[1]);
        $this->assertInstanceOf(TextMessageEndEvent::class, $this->sentEvents[2]);

        $this->assertSame($role, $this->sentEvents[0]->role);
        $this->assertSame($delta, $this->sentEvents[1]->delta);
    }

    public function test_manual_message_control_with_automatic_message_id(): void
    {
        $role = 'user';
        $delta = 'Test content';

        $messageId = $this->state->startMessage($role);
        $this->state->addMessageContent($delta);
        $this->state->finishMessage();

        $this->assertCount(3, $this->sentEvents);
        $this->assertInstanceOf(TextMessageStartEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(TextMessageContentEvent::class, $this->sentEvents[1]);
        $this->assertInstanceOf(TextMessageEndEvent::class, $this->sentEvents[2]);

        $this->assertSame($role, $this->sentEvents[0]->role);
        $this->assertSame($delta, $this->sentEvents[1]->delta);
        $this->assertSame($messageId, $this->sentEvents[1]->messageId);
        $this->assertSame($messageId, $this->sentEvents[2]->messageId);
    }

    public function test_add_tool_call_with_string_args(): void
    {
        $toolName = 'web_search';
        $args = '{"query": "test"}';
        $parentMessageId = 'msg_123';

        $toolCallId = $this->state->addToolCall($toolName, $args, null, $parentMessageId);

        $this->assertStringStartsWith('tool_', $toolCallId);
        $this->assertCount(3, $this->sentEvents);

        $this->assertInstanceOf(ToolCallStartEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(ToolCallArgsEvent::class, $this->sentEvents[1]);
        $this->assertInstanceOf(ToolCallEndEvent::class, $this->sentEvents[2]);

        $this->assertSame($toolName, $this->sentEvents[0]->toolCallName);
        $this->assertSame($parentMessageId, $this->sentEvents[0]->parentMessageId);
        $this->assertSame($args, $this->sentEvents[1]->delta);
    }

    public function test_manual_tool_call_control(): void
    {
        $toolName = 'file_reader';
        $args = '{"path": "/tmp/file.txt"}';

        $toolCallId = $this->state->startToolCall($toolName);
        $this->state->addToolCallArgs($args, $toolCallId);
        $this->state->finishToolCall($toolCallId);

        $this->assertCount(3, $this->sentEvents);
        $this->assertInstanceOf(ToolCallStartEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(ToolCallArgsEvent::class, $this->sentEvents[1]);
        $this->assertInstanceOf(ToolCallEndEvent::class, $this->sentEvents[2]);

        $this->assertSame($toolName, $this->sentEvents[0]->toolCallName);
        $this->assertSame($args, $this->sentEvents[1]->delta);
    }

    public function test_manual_tool_call_control_with_automatic_tool_id(): void
    {
        $toolName = 'file_reader';
        $args = '{"path": "/tmp/file.txt"}';

        $toolCallId = $this->state->startToolCall($toolName);
        $this->state->addToolCallArgs($args);
        $this->state->finishToolCall();

        $this->assertCount(3, $this->sentEvents);
        $this->assertInstanceOf(ToolCallStartEvent::class, $this->sentEvents[0]);
        $this->assertInstanceOf(ToolCallArgsEvent::class, $this->sentEvents[1]);
        $this->assertInstanceOf(ToolCallEndEvent::class, $this->sentEvents[2]);

        $this->assertSame($toolName, $this->sentEvents[0]->toolCallName);
        $this->assertSame($args, $this->sentEvents[1]->delta);
        $this->assertSame($toolCallId, $this->sentEvents[1]->toolCallId);
        $this->assertSame($toolCallId, $this->sentEvents[2]->toolCallId);
    }
}
