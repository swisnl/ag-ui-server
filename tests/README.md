# Test Suite

This test suite comprehensively covers the AG-UI Server package functionality.

## Test Structure

### Unit Tests (`tests/Unit/`)

**Events Tests:**
- `TextMessageStartEventTest` - Tests text message start event creation, serialization, and PSR-14 compatibility
- `TextMessageContentEventTest` - Tests content delta events including Unicode support

**Core Components:**
- `AgUiStateSimpleTest` - Tests the main state management class including:
  - Run lifecycle (start/finish/error)
  - Step management 
  - Message handling (string, closures, manual control)
  - Tool call management
- `DeltaBufferTest` - Tests adaptive buffering including:
  - Threshold-based flushing
  - Multi-message handling
  - Buffer accumulation

**Transporters:**
- `SseTransporterSimpleTest` - Tests SSE transport including:
  - JSON event formatting
  - Comment handling
  - Header configuration

### Integration Tests (`tests/Integration/`)

**Complete Workflows:**
- `CompleteWorkflowTest` - End-to-end RAG workflow testing including:
  - Multi-step process flows
  - Error handling scenarios
  - Performance with streaming content
  - Event ordering and JSON validation

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run specific test suite
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Integration/

# Run with coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

## Test Philosophy

- **Unit tests** focus on individual component behavior with mocked dependencies
- **Integration tests** verify complete workflows and real-world scenarios
- **Event tests** ensure AG-UI protocol compliance and JSON structure
- **Performance tests** validate buffering and streaming optimizations

## Coverage

The test suite covers:
- ✅ All AG-UI event types and serialization
- ✅ State management and lifecycle handling
- ✅ Transporter abstraction and SSE implementation
- ✅ Delta buffering and performance optimization
- ✅ Error handling and recovery scenarios
- ✅ PSR-14 event interface compliance
- ✅ Real-world RAG integration patterns