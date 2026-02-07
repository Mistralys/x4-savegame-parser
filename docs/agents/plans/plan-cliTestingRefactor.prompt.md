# Plan: Enable CLI Command Testing by Decoupling from Climate's Argument Parser

## Problem Statement

Tests in `CommandExecutionTest.php` are skipped because league/climate's `$cli->arguments->get('save')` returns empty in PHPUnit context even when `$_SERVER['argv']` is set. Currently, approximately 30+ tests are skipped with the message "Test requires CLI argument parsing which does not work in PHPUnit test context due to league/climate library limitation". This makes the test suite incomplete and prevents verification of critical CLI functionality.

The root cause is that league/climate's argument parsing doesn't work correctly when `$_SERVER['argv']` is modified after the test runner has started. While the CLI commands work perfectly in real CLI usage, they cannot be properly tested in PHPUnit.

## Solution Overview

Decouple QueryHandler's business logic from CLImate's argument parsing API by introducing a parameter value object. This allows tests to inject parameters directly while keeping the CLI interface unchanged for actual command-line usage.

## Steps

### 1. Create QueryParameters Value Object

**File**: `src/X4/SaveViewer/CLI/QueryParameters.php`

Create an immutable value object to hold all CLI parameters:

**Properties** (all readonly for immutability):
- `string $saveIdentifier` - Save name or ID (empty string if not provided)
- `string $filter` - JMESPath filter expression (empty string if not provided)
- `int $limit` - Maximum number of results (0 if not provided)
- `int $offset` - Number of results to skip (0 if not provided)
- `string $cacheKey` - Cache key for result reuse (empty string if not provided)
- `bool $isPretty` - Pretty-print JSON output flag
- `string $saves` - Space-separated list of save names/IDs for queue-extraction
- `bool $listFlag` - List queue contents flag (for queue-extraction)
- `bool $clearFlag` - Clear the queue flag (for queue-extraction)

**Static Factory Methods**:

1. `fromCLImate(CLImate $cli, string $command): self`
   - Extracts all parameter values from CLImate's argument parser
   - Handles the climate-specific API for getting arguments
   - Used by `QueryHandler::handle()` for real CLI usage

2. `forTest(array $params = []): self`
   - Creates instances with explicit values for unit tests
   - Accepts associative array with optional parameter overrides
   - Provides sensible defaults (empty strings, 0, false)
   - Example: `QueryParameters::forTest(['saveIdentifier' => 'quicksave', 'limit' => 10])`

**Benefits**:
- Immutable design prevents accidental parameter modification during execution
- Clear contract for what parameters are available
- Easy to construct in tests without touching CLImate
- Single source of truth for parameter structure

---

### 2. Refactor QueryHandler to Accept QueryParameters

**File**: `src/X4/SaveViewer/CLI/QueryHandler.php`

**Changes**:

1. Add new public method:
   ```php
   public function executeCommand(string $command, QueryParameters $params): string
   ```
   - Contains all business logic (validation, execution, filtering, pagination, caching)
   - Returns JSON string instead of echoing it
   - Accepts QueryParameters instead of reading from `$this->cli->arguments`
   - Throws QueryValidationException on errors

2. Refactor existing `handle()` method:
   ```php
   public function handle(): void
   {
       $this->cli->arguments->parse();
       $command = $this->getCommand();
       
       if ($command === null) {
           $this->cli->usage();
           return;
       }
       
       try {
           $params = QueryParameters::fromCLImate($this->cli, $command);
           $output = $this->executeCommand($command, $params);
           echo $output;
       } catch (QueryValidationException $e) {
           echo JsonResponseBuilder::error($e, $command, $params->isPretty);
           if (!Config::isTestSuiteEnabled()) {
               exit(1);
           }
       }
   }
   ```
   - Remains the CLI entry point
   - Creates QueryParameters from CLImate
   - Calls new `executeCommand()` method
   - Handles output and error formatting

3. Update all execution methods to accept QueryParameters:
   - Replace `$this->cli->arguments->get('save')` with `$params->saveIdentifier`
   - Replace `$this->cli->arguments->get('filter')` with `$params->filter`
   - Replace `$this->cli->arguments->get('limit')` with `$params->limit`
   - Replace `$this->cli->arguments->get('offset')` with `$params->offset`
   - Replace `$this->cli->arguments->get('cache-key')` with `$params->cacheKey`
   - Replace `$this->isPretty()` with `$params->isPretty`
   - Replace queue-extraction parameter access with `$params->saves`, `$params->listFlag`, `$params->clearFlag`

4. Update helper methods:
   - `applyFilteringAndPagination()` accepts QueryParameters
   - Remove `isPretty()` method (use `$params->isPretty` directly)
   - Update `outputSuccess()` to return string instead of echoing

**Benefits**:
- Backward compatible - `handle()` method signature unchanged
- Business logic completely decoupled from CLImate
- Testable without any CLI simulation
- Cleaner separation of concerns (parsing vs. execution)

---

### 3. Update CommandExecutionTest to Use Direct Execution

**File**: `tests/testsuites/CLI/CommandExecutionTest.php`

**Changes**:

1. Remove `simulateCLIArguments()` method (no longer needed)

2. Remove `requiresWorkingCLIArgParsing()` method (problem solved)

3. Add helper method to execute commands:
   ```php
   private function executeCommand(string $command, array $params = []): array
   {
       $this->handler = new QueryHandler($this->manager);
       $queryParams = QueryParameters::forTest($params);
       $output = $this->handler->executeCommand($command, $queryParams);
       return json_decode($output, true);
   }
   ```

4. Update all test methods to use direct execution:
   ```php
   // Before (skipped):
   public function test_queryCommand_shipsWithValidSave(): void
   {
       $this->requiresWorkingCLIArgParsing();
       $save = $this->getTestSave();
       
       ob_start();
       $this->simulateCLIArguments(['ships', '--save=' . self::TEST_SAVE_NAME]);
       $this->handler->handle();
       $output = ob_get_clean();
       
       $json = json_decode($output, true);
       // assertions...
   }
   
   // After (runs successfully):
   public function test_queryCommand_shipsWithValidSave(): void
   {
       $save = $this->getTestSave();
       
       $json = $this->executeCommand('ships', [
           'saveIdentifier' => self::TEST_SAVE_NAME
       ]);
       
       $this->assertIsArray($json);
       $this->assertTrue($json['success'] ?? false);
       // assertions...
   }
   ```

5. Update all ~30 skipped tests to use the new pattern

6. Keep tests for special commands (`list-saves`, `clear-cache`) that don't require save parameter

7. Update filter/pagination tests:
   ```php
   $json = $this->executeCommand('ships', [
       'saveIdentifier' => self::TEST_SAVE_NAME,
       'filter' => '[?contains_i(name, \'scout\')]',
       'limit' => 5,
       'offset' => 0
   ]);
   ```

**Benefits**:
- All tests run successfully (no more skipped tests)
- Tests are faster (no output buffering, no argv manipulation)
- Tests are more readable (direct parameter passing)
- Tests are more maintainable (no CLI simulation fragility)

---

### 4. Create Integration Test Suite for Real CLI

**File**: `tests/testsuites/CLI/Integration/RealCLIIntegrationTest.php`

Create a separate test suite that actually invokes the `bin/query` script as a subprocess to verify real CLI argument parsing works end-to-end.

**Test Strategy**:
- Use `proc_open()` or `exec()` to run actual CLI commands
- Parse JSON output from stdout
- Verify exit codes
- Test representative commands (not exhaustive)

**Example Tests**:
1. `test_realCLI_shipsCommand_withValidSave()` - Verify ships command works
2. `test_realCLI_withFilter_parsesCorrectly()` - Verify JMESPath filter parsing
3. `test_realCLI_withPagination_parsesCorrectly()` - Verify limit/offset parsing
4. `test_realCLI_invalidCommand_returnsError()` - Verify error handling
5. `test_realCLI_missingSave_returnsError()` - Verify validation errors

**Example Implementation**:
```php
/**
 * @group integration
 */
class RealCLIIntegrationTest extends TestCase
{
    private function runCLI(array $args): array
    {
        $binPath = __DIR__ . '/../../../../bin/query.bat';
        $command = escapeshellcmd($binPath) . ' ' . implode(' ', array_map('escapeshellarg', $args));
        
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w']  // stderr
            ],
            $pipes,
            dirname($binPath)
        );
        
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        
        return [
            'output' => $output,
            'errors' => $errors,
            'exitCode' => $exitCode,
            'json' => json_decode($output, true)
        ];
    }
    
    public function test_realCLI_shipsCommand_withValidSave(): void
    {
        $result = $this->runCLI(['ships', '--save=quicksave']);
        
        $this->assertEquals(0, $result['exitCode'], 'Command should exit with 0');
        $this->assertIsArray($result['json'], 'Output should be valid JSON');
        $this->assertTrue($result['json']['success'] ?? false);
    }
}
```

**Benefits**:
- Validates CLI integration layer works outside PHPUnit
- Catches CLImate-specific issues
- Verifies Windows batch scripts work correctly
- Documents expected CLI behavior for users

---

### 5. Update phpunit.xml Test Suite Configuration

**File**: `phpunit.xml`

Add separate test suite for integration tests:

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/testsuites</directory>
        <exclude>tests/testsuites/CLI/Integration</exclude>
    </testsuite>
    
    <testsuite name="Integration">
        <directory>tests/testsuites/CLI/Integration</directory>
    </testsuite>
</testsuites>
```

**Running Tests**:
- Fast unit tests only: `vendor/bin/phpunit --testsuite=Unit`
- Integration tests only: `vendor/bin/phpunit --testsuite=Integration`
- All tests: `vendor/bin/phpunit`
- Skip integration during development: `vendor/bin/phpunit --exclude-group integration`

**Benefits**:
- Fast feedback loop during development (unit tests ~1-10ms each)
- Clear separation of test types
- CI can run integration tests separately
- Developers can skip slow tests when needed

---

## Further Considerations

### 1. Backward Compatibility
- The `handle()` method signature remains unchanged
- Existing CLI scripts (`bin/query`) continue working without modification
- Only internal implementation changes
- No breaking changes to public API

### 2. QueryParameters Immutability
- Use readonly properties (PHP 8.1+) to ensure immutability
- Prevents accidental parameter mutation during test execution
- Ensures thread-safety if used in async contexts
- Makes debugging easier (parameters can't change unexpectedly)

### 3. Integration Test Performance
- Subprocess tests are significantly slower (~100-500ms per test vs ~1-10ms for unit tests)
- Mark with `@group integration` annotation
- Document in README how to skip during rapid development
- Consider running integration tests only in CI/pre-commit hooks
- Keep integration test count small (5-10 representative tests)

### 4. Error Handling
- QueryValidationException should still be thrown by business logic
- `executeCommand()` method signature: `public function executeCommand(string $command, QueryParameters $params): string`
- Let exceptions bubble up to caller (test can catch or let PHPUnit handle)
- CLI `handle()` method catches and formats errors as JSON

### 5. Testing Strategy
- Unit tests: Fast, comprehensive coverage of all commands and parameters
- Integration tests: Slow, smoke tests for CLI interface verification
- Aim for >90% code coverage with unit tests alone
- Integration tests catch environment-specific issues (Windows vs Linux, path issues, etc.)

### 6. Documentation Updates
- Update CLI API reference docs to mention QueryParameters
- Add code examples showing how to test CLI commands
- Document the separation between unit and integration tests
- Add troubleshooting section for CLI argument parsing issues

---

## Implementation Order

1. **Create QueryParameters class** - Foundation for everything else
2. **Refactor QueryHandler** - Add executeCommand() method, update handle()
3. **Update CommandExecutionTest** - Convert all tests to use direct execution
4. **Validate with test run** - Ensure all previously skipped tests now pass
5. **Create integration tests** - Add RealCLIIntegrationTest with subprocess execution
6. **Update phpunit.xml** - Add test suite configuration
7. **Run full test suite** - Verify everything works together
8. **Update documentation** - Document the changes and new testing approach

---

## Success Criteria

- [ ] All previously skipped tests in CommandExecutionTest now run and pass
- [ ] No tests call `requiresWorkingCLIArgParsing()` (method can be removed)
- [ ] QueryParameters class exists with immutable properties
- [ ] QueryHandler::executeCommand() method exists and is public
- [ ] Integration tests verify real CLI usage
- [ ] Test suite can be run with/without integration tests
- [ ] Existing CLI scripts work unchanged
- [ ] Test execution time for unit tests is fast (<1 second for all CLI tests)
- [ ] Code coverage for CLI components is >90%
- [ ] Documentation updated with new testing approach

---

## Alternative Approaches Considered

### Alternative 1: Mock CLImate Directly
Instead of refactoring, create a mock CLImate class that bypasses argument parsing.

**Pros**:
- Less refactoring required
- No changes to QueryHandler

**Cons**:
- Fragile - couples tests to CLImate's internal API
- Doesn't improve code design
- Still requires understanding CLImate internals
- Mock maintenance burden when CLImate updates

**Decision**: Rejected - Refactoring to use QueryParameters is cleaner and improves design

### Alternative 2: Subprocess Testing Only
Skip unit testing entirely, only test via subprocess invocation.

**Pros**:
- Tests exactly what users experience
- No refactoring needed

**Cons**:
- Very slow (~100-500ms per test × 30 tests = 15+ seconds)
- Harder to debug failures
- Platform-specific issues (Windows batch scripts, path separators)
- Poor feedback loop during development

**Decision**: Rejected - Need fast unit tests for developer productivity

### Alternative 3: Duplicate Business Logic in Tests
Extract business logic to separate classes and test those, leave CLI layer untested.

**Pros**:
- Clear separation of concerns
- Testable business logic

**Cons**:
- Doesn't test the actual QueryHandler that's used in production
- Duplication of logic
- CLI layer remains untested

**Decision**: Rejected - Want to test the actual production code path

---

## Risk Assessment

### Low Risk
- QueryParameters is a simple value object with no complex logic
- Refactoring is primarily mechanical (replace method calls)
- Backward compatibility maintained (handle() unchanged)

### Medium Risk
- Integration tests may be flaky on different environments
- Need to ensure subprocess tests clean up properly
- Windows batch script execution in tests needs validation

### Mitigation Strategies
- Thorough testing on Windows before committing
- Add cleanup in integration test tearDown()
- Document environment requirements for integration tests
- Make integration tests skippable via annotation

