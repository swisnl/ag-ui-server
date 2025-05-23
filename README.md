# AG-UI Server for PHP

[![PHP from Packagist](https://img.shields.io/packagist/php-v/swisnl/ag-ui-server.svg)](https://packagist.org/packages/swisnl/ag-ui-server)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/swisnl/ag-ui-server.svg)](https://packagist.org/packages/swisnl/ag-ui-server)
[![Software License](https://img.shields.io/packagist/l/swisnl/ag-ui-server.svg)](LICENSE.md)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen.svg)](https://plant.treeware.earth/swisnl/ag-ui-server)
[![Build Status](https://img.shields.io/github/actions/workflow/status/swisnl/ag-ui-server/run-tests.yml?label=tests&branch=master)](https://github.com/swisnl/ag-ui-server/actions/workflows/run-tests.yml)
[![Made by SWIS](https://img.shields.io/badge/%F0%9F%9A%80-made%20by%20SWIS-%230737A9.svg)](https://www.swis.nl)

A PHP server integration package for [AG-UI](https://ag-ui.com/) - standardized AI agent frontend communication via Server-Sent Events and other transport methods.

AG-UI provides a real-time, event-driven protocol for streaming AI agent responses, tool calls, and state updates to frontends. This package makes it easy to integrate AG-UI into your PHP AI projects.

## Features

- **Complete AG-UI Event Support** - All event types: messages, tool calls, lifecycle, state management
- **Flexible Message API** - Simple one-shot messages or streaming with closures/iterables
- **Pluggable Transporters** - SSE included, easily extend with WebSocket, polling, etc.
- **Adaptive Delta Buffering** - Optimized streaming performance
- **PSR-14 Compatible** - Interoperable with existing event systems
- **Type Safe** - PHPStan types for better developer experience
- **Framework Agnostic** - Works with Laravel, Symfony, or any PHP application

## Installation

```bash
composer require swis/ag-ui-server
```

## Quick Start

```php
<?php

use Swis\AgUiServer\AgUiState;
use Swis\AgUiServer\Transporter\SseTransporter;

// Initialize state
$state = new AgUiState();

// Start a conversation run
$threadId = 'thread_' . uniqid();
$runId = 'run_' . uniqid();
$state->startRun($threadId, $runId);

// Simple message
$state->addMessage('Hello! How can I help you?');

// Streaming message from LLM
$state->addMessage(function() {
    return $this->llm->streamCompletion($prompt); // Returns iterable
});

// Tool call
$state->addToolCall('web_search', '{"query": "weather today"}');

// Finish the run
$state->finishRun();
```

## Core Concepts

### AG-UI Events

The package supports all AG-UI event types:

- **Lifecycle**: `RunStarted`, `RunFinished`, `RunError`, `StepStarted`, `StepFinished`
- **Messages**: `TextMessageStart`, `TextMessageContent`, `TextMessageEnd`
- **Tool Calls**: `ToolCallStart`, `ToolCallArgs`, `ToolCallEnd`
- **State**: `StateSnapshot`, `StateDelta`, `MessagesSnapshot`
- **Special**: `Raw`, `Custom`

### Flexible Message API

```php
// String content - sent as single message
$state->addMessage('Complete response here');

// Closure returning string
$state->addMessage(function() {
    return $this->llm->complete($prompt);
});

// Closure returning iterable - streamed as deltas
$state->addMessage(function() {
    return $this->llm->streamCompletion($prompt);
};

// Direct iterable
$state->addMessage(['Hello ', 'world', '!']);

// Manual control for complex scenarios
$messageId = $state->startMessage();
foreach ($complexStream as $chunk) {
    $state->addMessageContent($messageId, $chunk);
}
$state->finishMessage($messageId);
```

### Transporters

Easily swap transport methods:

```php
// Server-Sent Events (default)
$transporter = new SseTransporter();
$transporter->initialize(); // Sends headers

// Custom headers
$transporter = new SseTransporter([
    'Access-Control-Allow-Origin' => 'https://yourapp.com',
    'Cache-Control' => 'no-cache',
]);
$transporter->initialize(); // Sends headers

// Future: WebSocket support
// $transporter = new WebSocketTransporter($connection);
```

## RAG Integration Example

```php
public function handleChatRequest(Request $request)
{
    $userMessage = $request->input('message');
    $threadId = $request->input('threadId') ?? 'thread_' . uniqid();
    $runId = 'run_' . uniqid();
    
    $state = new AgUiState();
    $state->startRun($threadId, $runId);
    
    try {
        // Step 1: Analyze query
        $state->startStep('analyzing_query');
        // ... analysis logic ...
        $state->finishStep();
        
        // Step 2: Retrieve context
        $state->startStep('retrieving_context');
        $state->addToolCall(null, 'vector_search', json_encode([
            'query' => $userMessage,
            'top_k' => 5
        ]));
        $documents = $this->vectorSearch($userMessage);
        $state->finishStep();
        
        // Step 3: Generate response
        $state->startStep('generating_response');
        $state->addMessage(null, function() use ($userMessage, $documents) {
            return $this->llm->streamWithContext($userMessage, $documents);
        }, 'assistant');
        $state->finishStep();
        
        $state->finishRun();
        
    } catch (\Exception $e) {
        $state->errorRun($e->getMessage());
    }
}
```

## Advanced Features

### Manual Event Triggering

While `AgUiState` provides a convenient high-level API for typical AI workflows, you can also trigger events manually for complete control over your application's behavior:

```php
use Swis\AgUiServer\Events\TextMessageStartEvent;
use Swis\AgUiServer\Events\TextMessageContentEvent; 
use Swis\AgUiServer\Events\TextMessageEndEvent;
use Swis\AgUiServer\Transporter\SseTransporter;
use Psr\EventDispatcher\EventDispatcherInterface;

// Set up event dispatcher and transporter
$dispatcher = $container->get(EventDispatcherInterface::class);
$transporter = new SseTransporter();
$transporter->initialize(); // Sends headers
$transporter->setEventDispatcher($dispatcher);

// Now trigger events from your application
$messageId = 'msg_' . uniqid();

// The transporter will automatically listen for these events and send them
$dispatcher->dispatch(new TextMessageStartEvent(
    messageId: $messageId,
    role: 'assistant'
));

$dispatcher->dispatch(new TextMessageContentEvent(
    messageId: $messageId,
    content: 'Hello from my custom application!'
));

$dispatcher->dispatch(new TextMessageEndEvent(
    messageId: $messageId
));
```

**Direct Event Dispatching (Without PSR-14)**

If you prefer not to use PSR-14, you can send events directly:

```php
$transporter = new SseTransporter();
$transporter->initialize();

// Send events directly
$event = new TextMessageStartEvent( 
    messageId: 'msg_123',
    role: 'assistant'
);

$transporter->send($event);
$transporter->close();
```

**When to Use Manual Events vs AgUiState**

- **Use `AgUiState`** for typical AI agent workflows with automatic state management, message streaming, and tool calls
- **Use manual events** when you need:
  - Complete control over event timing and data
  - Integration with existing event-driven architectures
  - Custom event flows that don't fit the standard AI agent pattern
  - Building your own higher-level abstractions

### Adaptive Delta Buffering

Delta buffering is optional and can be enabled to optimize streaming performance:

```php
// Without delta buffering (default)
$state = new AgUiState();

// With delta buffering for optimized streaming
$state = (new AgUiState())->withDeltaBuffering(
    deltaBufferThreshold: 150,  // chars
    deltaFlushInterval: 0.3     // seconds
);
```

### PSR-14 Event Integration

All AG-UI events implement PSR-14 interfaces:

```php
use Psr\EventDispatcher\EventDispatcherInterface;

// Your existing PSR-14 dispatcher
$dispatcher = $container->get(EventDispatcherInterface::class);

// Listen to AG-UI events
$dispatcher->addListener(TextMessageStartEvent::class, function($event) {
    // Log message start
    $this->logger->info('Message started', ['messageId' => $event->messageId]);
});
```

### Custom Transporters

Implement your own transport:

```php
class WebSocketTransporter implements TransporterInterface
{
    public function __construct(private $connection) {}
    
    public function initialize(): void
    {
        // Setup WebSocket connection
    }
    
    public function send(AgUiEvent $event): void
    {
        $this->connection->send($event->toJson());
    }
    
    public function sendComment(string $comment): void
    {
        // WebSocket doesn't need comments
    }
    
    public function close(): void
    {
        $this->connection->close();
    }
}
```

## Event Types Reference

### Message Events
- `TextMessageStart` - Begin streaming a message
- `TextMessageContent` - Content chunk (delta)
- `TextMessageEnd` - Message complete

### Tool Call Events
- `ToolCallStart` - Begin tool execution
- `ToolCallArgs` - Tool arguments (can be streamed)
- `ToolCallEnd` - Tool execution complete

### Lifecycle Events
- `RunStarted` - Agent run begins
- `RunFinished` - Agent run completes successfully
- `RunError` - Agent run failed
- `StepStarted` - Processing step begins
- `StepFinished` - Processing step completes

### State Events
- `StateSnapshot` - Complete state
- `StateDelta` - State changes (JSON Patch)
- `MessagesSnapshot` - All conversation messages

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Joris Meijer](https://github.com/jormeijer)
- [All Contributors](../../contributors)

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
