<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Swis\AgUiServer\Events\TextMessageStartEvent;

class TextMessageStartEventTest extends TestCase
{
    public function test_creates_event_with_required_parameters(): void
    {
        $messageId = 'msg_123';
        $event = new TextMessageStartEvent($messageId);

        $this->assertSame('TextMessageStart', $event->type);
        $this->assertSame($messageId, $event->messageId);
        $this->assertSame('assistant', $event->role);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->timestamp);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function test_creates_event_with_custom_role(): void
    {
        $messageId = 'msg_123';
        $role = 'user';
        $event = new TextMessageStartEvent($messageId, $role);

        $this->assertSame($role, $event->role);
    }

    public function test_creates_event_with_custom_timestamp(): void
    {
        $messageId = 'msg_123';
        $timestamp = new \DateTimeImmutable('2023-01-01T00:00:00Z');
        $event = new TextMessageStartEvent($messageId, 'assistant', $timestamp);

        $this->assertSame($timestamp, $event->timestamp);
    }

    public function test_creates_event_with_raw_event_data(): void
    {
        $messageId = 'msg_123';
        $rawEvent = ['custom' => 'data'];
        $event = new TextMessageStartEvent($messageId, 'assistant', null, $rawEvent);

        $this->assertSame($rawEvent, $event->rawEvent);
    }

    public function test_converts_to_array_with_snake_case_type(): void
    {
        $messageId = 'msg_123';
        $event = new TextMessageStartEvent($messageId, 'user');

        $array = $event->toArray();

        $this->assertSame('TEXT_MESSAGE_START', $array['type']);
        $this->assertSame($messageId, $array['messageId']);
        $this->assertSame('user', $array['role']);
        $this->assertArrayHasKey('timestamp', $array);
    }

    public function test_converts_to_json(): void
    {
        $messageId = 'msg_123';
        $event = new TextMessageStartEvent($messageId);

        $json = $event->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('TEXT_MESSAGE_START', $decoded['type']);
        $this->assertSame($messageId, $decoded['messageId']);
        $this->assertSame('assistant', $decoded['role']);
    }

    public function test_can_stop_propagation(): void
    {
        $event = new TextMessageStartEvent('msg_123');

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }
}
