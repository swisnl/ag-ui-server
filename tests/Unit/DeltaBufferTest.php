<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\AgUiServer\DeltaBuffer;
use Swis\AgUiServer\Events\TextMessageContentEvent;
use Swis\AgUiServer\Transporter\TransporterInterface;

class DeltaBufferTest extends TestCase
{
    private TransporterInterface $mockTransporter;

    private DeltaBuffer $deltaBuffer;

    protected function setUp(): void
    {
        $this->mockTransporter = $this->createMock(TransporterInterface::class);
        $this->deltaBuffer = new DeltaBuffer($this->mockTransporter, 100, 0.2);
    }

    public function test_buffers_small_deltas(): void
    {
        $messageId = 'msg_123';

        // Should not send immediately for small content
        $this->mockTransporter->expects($this->never())->method('sendEvent');

        $this->deltaBuffer->add($messageId, 'Hello');
        $this->deltaBuffer->add($messageId, ' world');
    }

    public function test_flushes_when_threshold_reached(): void
    {
        $messageId = 'msg_123';
        $longContent = str_repeat('a', 101); // Exceeds threshold of 100

        $this->mockTransporter->expects($this->once())
            ->method('sendEvent')
            ->with($this->callback(function ($event) use ($messageId, $longContent) {
                return $event instanceof TextMessageContentEvent
                    && $event->messageId === $messageId
                    && $event->delta === $longContent;
            }));

        $this->deltaBuffer->add($messageId, $longContent);
    }

    public function test_flushes_accumulated_content(): void
    {
        $messageId = 'msg_123';

        // Add content that doesn't reach threshold individually
        $this->deltaBuffer->add($messageId, 'Hello');
        $this->deltaBuffer->add($messageId, ' ');
        $this->deltaBuffer->add($messageId, 'world');

        $this->mockTransporter->expects($this->once())
            ->method('sendEvent')
            ->with($this->callback(function ($event) use ($messageId) {
                return $event instanceof TextMessageContentEvent
                    && $event->messageId === $messageId
                    && $event->delta === 'Hello world';
            }));

        $this->deltaBuffer->flush($messageId);
    }

    public function test_handles_multiple_messages(): void
    {
        $messageId1 = 'msg_123';
        $messageId2 = 'msg_456';

        $this->deltaBuffer->add($messageId1, 'First message');
        $this->deltaBuffer->add($messageId2, 'Second message');

        $sentEvents = [];
        $this->mockTransporter->method('sendEvent')
            ->willReturnCallback(function ($event) use (&$sentEvents) {
                $sentEvents[] = $event;
            });

        $this->deltaBuffer->flush($messageId1);
        $this->deltaBuffer->flush($messageId2);

        $this->assertCount(2, $sentEvents);
        $this->assertSame($messageId1, $sentEvents[0]->messageId);
        $this->assertSame('First message', $sentEvents[0]->delta);
        $this->assertSame($messageId2, $sentEvents[1]->messageId);
        $this->assertSame('Second message', $sentEvents[1]->delta);
    }

    public function test_flush_all_flushes_all_buffers(): void
    {
        $this->deltaBuffer->add('msg_1', 'Content 1');
        $this->deltaBuffer->add('msg_2', 'Content 2');

        $this->mockTransporter->expects($this->exactly(2))->method('sendEvent');

        $this->deltaBuffer->flushAll();
    }

    public function test_does_not_flush_empty_buffer(): void
    {
        $this->mockTransporter->expects($this->never())->method('sendEvent');

        $this->deltaBuffer->flush('nonexistent_message');
    }
}
