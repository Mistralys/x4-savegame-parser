<?php

declare(strict_types=1);

namespace testsuites\CLI;

use Mistralys\X4\SaveViewer\CLI\JsonResponseBuilder;
use PHPUnit\Framework\TestCase;
use Exception;
use AppUtils\BaseException;

class JsonResponseBuilderTest extends TestCase
{
    public function test_success_hasAllRequiredFields(): void
    {
        $response = JsonResponseBuilder::success('test-command', ['item' => 'value']);
        $json = json_decode($response, true);

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('version', $json);
        $this->assertArrayHasKey('timestamp', $json);
        $this->assertEquals('test-command', $json['command']);
        $this->assertEquals(['item' => 'value'], $json['data']);
    }

    public function test_success_versionMatchesVersionFile(): void
    {
        $response = JsonResponseBuilder::success('test', []);
        $json = json_decode($response, true);

        $versionFile = __DIR__ . '/../../../VERSION';
        $expectedVersion = trim(file_get_contents($versionFile));

        $this->assertEquals($expectedVersion, $json['version']);
    }

    public function test_success_timestampIsValidISO8601(): void
    {
        $response = JsonResponseBuilder::success('test', []);
        $json = json_decode($response, true);

        // Should be able to parse as DateTime
        $dt = \DateTime::createFromFormat(\DateTime::ATOM, $json['timestamp']);
        $this->assertNotFalse($dt, 'Timestamp should be valid ISO 8601');
    }

    public function test_success_paginationIncludedWhenProvided(): void
    {
        $pagination = [
            'total' => 100,
            'limit' => 10,
            'offset' => 0,
            'hasMore' => true
        ];

        $response = JsonResponseBuilder::success('test', [], $pagination);
        $json = json_decode($response, true);

        $this->assertArrayHasKey('pagination', $json);
        $this->assertEquals($pagination, $json['pagination']);
    }

    public function test_success_paginationNotIncludedByDefault(): void
    {
        $response = JsonResponseBuilder::success('test', []);
        $json = json_decode($response, true);

        $this->assertArrayNotHasKey('pagination', $json);
    }

    public function test_error_hasAllRequiredFields(): void
    {
        $exception = new Exception('Test error', 12345);
        $response = JsonResponseBuilder::error($exception, 'test-command');
        $json = json_decode($response, true);

        $this->assertFalse($json['success']);
        $this->assertArrayHasKey('version', $json);
        $this->assertArrayHasKey('timestamp', $json);
        $this->assertEquals('test-command', $json['command']);
        $this->assertEquals('error', $json['type']);
        $this->assertEquals('Test error', $json['message']);
        $this->assertEquals(12345, $json['code']);
        $this->assertIsArray($json['errors']);
    }

    public function test_error_commandIsOptional(): void
    {
        $exception = new Exception('Test error');
        $response = JsonResponseBuilder::error($exception);
        $json = json_decode($response, true);

        $this->assertArrayNotHasKey('command', $json);
    }

    public function test_error_includesExceptionChain(): void
    {
        $innerException = new Exception('Inner error', 100);
        $outerException = new Exception('Outer error', 200, $innerException);

        $response = JsonResponseBuilder::error($outerException);
        $json = json_decode($response, true);

        $this->assertCount(2, $json['errors']);

        // First error should be the outer exception
        $this->assertEquals('Outer error', $json['errors'][0]['message']);
        $this->assertEquals(200, $json['errors'][0]['code']);
        $this->assertEquals(Exception::class, $json['errors'][0]['class']);
        $this->assertArrayHasKey('trace', $json['errors'][0]);

        // Second error should be the inner exception
        $this->assertEquals('Inner error', $json['errors'][1]['message']);
        $this->assertEquals(100, $json['errors'][1]['code']);
    }

    public function test_error_includesBaseExceptionDetails(): void
    {
        $exception = new BaseException(
            'Test base exception',
            'Additional details here',
            12345
        );

        $response = JsonResponseBuilder::error($exception);
        $json = json_decode($response, true);

        $this->assertArrayHasKey('details', $json['errors'][0]);
        $this->assertEquals('Additional details here', $json['errors'][0]['details']);
    }

    public function test_prettyPrintingWorks(): void
    {
        $response = JsonResponseBuilder::success('test', ['item' => 'value'], null, true);

        // Pretty-printed JSON should contain newlines
        $this->assertStringContainsString("\n", $response);

        // Should still be valid JSON
        $json = json_decode($response, true);
        $this->assertIsArray($json);
    }

    public function test_jsonEncodingUsesCorrectFlags(): void
    {
        $data = [
            'url' => 'http://example.com/path',
            'unicode' => 'Über'
        ];

        $response = JsonResponseBuilder::success('test', $data);

        // Should not escape slashes
        $this->assertStringContainsString('http://example.com/path', $response);

        // Should not escape unicode
        $this->assertStringContainsString('Über', $response);
    }
}
