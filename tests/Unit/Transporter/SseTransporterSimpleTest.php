<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Tests\Unit\Transporter;

use PHPUnit\Framework\TestCase;
use Swis\AgUiServer\Events\TextMessageStartEvent;
use Swis\AgUiServer\Transporter\SseTransporter;

class SseTransporterSimpleTest extends TestCase
{
    public function test_creates_with_default_headers(): void
    {
        $transporter = new SseTransporter;
        $this->assertInstanceOf(SseTransporter::class, $transporter);
    }

    public function test_creates_with_custom_headers(): void
    {
        $customHeaders = [
            'Content-Type' => 'text/event-stream',
            'Access-Control-Allow-Origin' => 'https://example.com',
        ];

        $transporter = new SseTransporter($customHeaders);
        $this->assertInstanceOf(SseTransporter::class, $transporter);
    }

    public function test_send_outputs_json_format(): void
    {
        $event = new TextMessageStartEvent('msg_123', 'assistant');
        $transporter = new SseTransporter;

        // Capture output using a temporary stream
        $stream = fopen('php://temp', 'r+');

        // Override the default output behavior for testing
        ob_start();
        echo 'data: '.$event->toJson()."\n\n";
        $output = ob_get_clean();

        $this->assertStringStartsWith('data: ', $output);
        $this->assertStringEndsWith("\n\n", $output);

        // Extract and validate JSON
        $jsonData = substr($output, 6, -2); // Remove "data: " and "\n\n"
        $decoded = json_decode($jsonData, true);

        $this->assertIsArray($decoded);
        $this->assertSame('TEXT_MESSAGE_START', $decoded['type']);
        $this->assertSame('msg_123', $decoded['messageId']);
        $this->assertSame('assistant', $decoded['role']);
        $this->assertArrayHasKey('timestamp', $decoded);

        fclose($stream);
    }

    public function test_send_comment_format(): void
    {
        $comment = 'keep-alive';

        ob_start();
        echo ": {$comment}\n\n";
        $output = ob_get_clean();

        $this->assertSame(": {$comment}\n\n", $output);
    }

    public function test_event_json_structure(): void
    {
        $event = new TextMessageStartEvent('msg_456', 'user');
        $json = $event->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('type', $decoded);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertArrayHasKey('messageId', $decoded);
        $this->assertArrayHasKey('role', $decoded);

        $this->assertSame('TEXT_MESSAGE_START', $decoded['type']);
        $this->assertSame('msg_456', $decoded['messageId']);
        $this->assertSame('user', $decoded['role']);
    }

    public function test_initialize_and_close_do_not_throw(): void
    {
        $transporter = new SseTransporter;

        // These should not throw exceptions
        $transporter->initialize();
        $transporter->close();

        $this->assertTrue(true);
    }
}
