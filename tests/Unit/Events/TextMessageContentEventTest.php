<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Swis\AgUiServer\Events\TextMessageContentEvent;

class TextMessageContentEventTest extends TestCase
{
    public function test_creates_event_with_required_parameters(): void
    {
        $messageId = 'msg_123';
        $delta = 'Hello world';
        $event = new TextMessageContentEvent($messageId, $delta);

        $this->assertSame('TextMessageContent', $event->type);
        $this->assertSame($messageId, $event->messageId);
        $this->assertSame($delta, $event->delta);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->timestamp);
    }

    public function test_converts_to_array_with_snake_case_type(): void
    {
        $messageId = 'msg_123';
        $delta = 'test content';
        $event = new TextMessageContentEvent($messageId, $delta);

        $array = $event->toArray();

        $this->assertSame('TEXT_MESSAGE_CONTENT', $array['type']);
        $this->assertSame($messageId, $array['messageId']);
        $this->assertSame($delta, $array['delta']);
        $this->assertArrayHasKey('timestamp', $array);
    }

    public function test_handles_empty_delta(): void
    {
        $messageId = 'msg_123';
        $event = new TextMessageContentEvent($messageId, '');

        $this->assertSame('', $event->delta);
        $this->assertSame('', $event->toArray()['delta']);
    }

    public function test_handles_unicode_content(): void
    {
        $messageId = 'msg_123';
        $delta = '🚀 Hello 世界';
        $event = new TextMessageContentEvent($messageId, $delta);

        $this->assertSame($delta, $event->delta);

        $json = $event->toJson();
        $decoded = json_decode($json, true);
        $this->assertSame($delta, $decoded['delta']);
    }
}
