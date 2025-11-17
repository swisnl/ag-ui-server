<?php

declare(strict_types=1);

namespace Swis\AgUiServer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Swis\AgUiServer\AgUiState;
use Swis\AgUiServer\Transporter\SseTransporter;

class CompleteWorkflowTest extends TestCase
{
    public function test_complete_rag_workflow(): void
    {
        $output = '';
        $mockTransporter = $this->createMock(\Swis\AgUiServer\Transporter\TransporterInterface::class);
        $mockTransporter->method('initialize');
        $mockTransporter->method('sendEvent')
            ->willReturnCallback(function ($event) use (&$output) {
                $output .= 'data: '.$event->toJson()."\n\n";
            });

        $state = new AgUiState($mockTransporter);

        $threadId = 'thread_'.uniqid();
        $runId = 'run_'.uniqid();

        // Complete RAG workflow
        $state->startRun($threadId, $runId);

        // Step 1: Analyze query
        $state->startStep('analyzing_query');
        $state->addMessage('Analyzing your question...');
        $state->finishStep();

        // Step 2: Retrieve context
        $state->startStep('retrieving_context');
        $toolCallId = $state->addToolCall('vector_search', function () {
            return json_encode(['query' => 'user question', 'top_k' => 5]);
        });
        $state->addMessage('Found relevant documents...');
        $state->finishStep();

        // Step 3: Generate response
        $state->startStep('generating_response');
        $state->addMessage(function () {
            return ['Based ', 'on ', 'the ', 'context, ', 'here ', 'is ', 'my ', 'answer...'];
        });
        $state->finishStep();

        $state->finishRun();

        // Verify the output contains expected events
        $this->assertStringContains('RUN_STARTED', $output);
        $this->assertStringContains('STEP_STARTED', $output);
        $this->assertStringContains('TEXT_MESSAGE_START', $output);
        $this->assertStringContains('TEXT_MESSAGE_CONTENT', $output);
        $this->assertStringContains('TEXT_MESSAGE_END', $output);
        $this->assertStringContains('TOOL_CALL_START', $output);
        $this->assertStringContains('TOOL_CALL_ARGS', $output);
        $this->assertStringContains('TOOL_CALL_END', $output);
        $this->assertStringContains('STEP_FINISHED', $output);
        $this->assertStringContains('RUN_FINISHED', $output);

        // Verify specific content
        $this->assertStringContains('analyzing_query', $output);
        $this->assertStringContains('retrieving_context', $output);
        $this->assertStringContains('generating_response', $output);
        $this->assertStringContains('vector_search', $output);
        $this->assertStringContains('Analyzing your question', $output);
        $this->assertStringContains('Found relevant documents', $output);

        // Verify JSON structure
        $lines = explode("\n", $output);
        $dataLines = array_filter($lines, fn ($line) => str_starts_with($line, 'data: '));

        foreach ($dataLines as $line) {
            $jsonData = substr($line, 6); // Remove "data: "
            $this->assertJson($jsonData, "Invalid JSON in line: $line");

            $decoded = json_decode($jsonData, true);
            $this->assertArrayHasKey('type', $decoded);
            $this->assertArrayHasKey('timestamp', $decoded);
        }
    }

    public function test_error_handling_workflow(): void
    {
        $output = '';
        $mockTransporter = $this->createMock(\Swis\AgUiServer\Transporter\TransporterInterface::class);
        $mockTransporter->method('initialize');
        $mockTransporter->method('sendEvent')
            ->willReturnCallback(function ($event) use (&$output) {
                $output .= 'data: '.$event->toJson()."\n\n";
            });

        $state = new AgUiState($mockTransporter);

        $threadId = 'thread_'.uniqid();
        $runId = 'run_'.uniqid();

        $state->startRun($threadId, $runId);
        $state->startStep('processing');

        // Simulate an error
        $state->errorRun('Processing failed', 'ERR_001');

        $this->assertStringContains('RUN_STARTED', $output);
        $this->assertStringContains('STEP_STARTED', $output);
        $this->assertStringContains('RUN_ERROR', $output);
        $this->assertStringContains('Processing failed', $output);
        $this->assertStringContains('ERR_001', $output);
    }

    public function test_streaming_performance(): void
    {
        $transporter = new SseTransporter();
        $state = (new AgUiState($transporter))->withDeltaBuffering(deltaBufferThreshold: 50, deltaFlushInterval: 0.1);

        $buffer = '';
        ob_start(function ($contents) use (&$buffer) {
            $buffer .= $contents;
        });

        $state->startRun('thread_perf', 'run_perf');

        // Test with large streaming content
        $messageId = $state->startMessage('assistant');

        $start = microtime(true);

        // Add many small chunks
        for ($i = 0; $i < 100; $i++) {
            $state->addMessageContent($messageId, "chunk{$i} ");
        }

        $state->finishMessage($messageId);
        $state->finishRun();

        $duration = microtime(true) - $start;
        $output = ob_get_clean();
        $output = $buffer.$output;

        // Should complete quickly due to buffering
        $this->assertLessThan(1.0, $duration, 'Streaming should be fast with buffering');

        // Should contain consolidated content events, not 100 separate ones
        $contentEventCount = substr_count($output, 'TEXT_MESSAGE_CONTENT');
        $this->assertLessThan(50, $contentEventCount, 'Should buffer content effectively');
    }

    private function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertThat(
            $haystack,
            $this->logicalOr(
                $this->stringContains($needle),
                $this->stringContains(strtolower($needle)),
                $this->stringContains(strtoupper($needle))
            ),
            $message ?: "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}
