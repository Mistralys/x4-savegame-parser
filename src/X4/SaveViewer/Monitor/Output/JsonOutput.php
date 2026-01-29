<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor\Output;

use AppUtils\BaseException;
use AppUtils\FileHelper;
use DateTime;
use DateTimeZone;

class JsonOutput implements MonitorOutputInterface
{
    private bool $loggingEnabled = false;
    private string $version;

    public function __construct()
    {
        $this->version = trim(FileHelper::readContents(__DIR__ . '/../../../../../VERSION'));
    }

    public function log(string $message, ...$args): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $this->writeLine([
            'type' => 'log',
            'level' => 'info',
            'message' => sprintf($message, ...$args)
        ]);
    }

    public function logHeader(string $title, ...$args): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $this->writeLine([
            'type' => 'log',
            'level' => 'info',
            'message' => sprintf($title, ...$args),
            'header' => true
        ]);
    }

    public function tick(int $counter): void
    {
        $this->writeLine([
            'type' => 'tick',
            'counter' => $counter
        ]);
    }

    public function notify(string $eventName, array $payload = []): void
    {
        $data = [
            'type' => 'event',
            'name' => $eventName,
            'payload' => $payload
        ];

        // Add version to MONITOR_STARTED event
        if ($eventName === 'MONITOR_STARTED') {
            $data['payload']['version'] = $this->version;
        }

        $this->writeLine($data);
    }

    public function error(\Throwable $e): void
    {
        $errors = [];
        $current = $e;

        // Build the exception chain from top to bottom
        while ($current !== null) {
            $errorData = [
                'message' => $current->getMessage(),
                'code' => $current->getCode(),
                'class' => get_class($current),
                'trace' => $current->getTraceAsString()
            ];

            // Add detailed debug information for BaseException
            if ($current instanceof BaseException) {
                $errorData['details'] = $current->getDetails();
            }

            $errors[] = $errorData;
            $current = $current->getPrevious();
        }

        $this->writeLine([
            'type' => 'error',
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'errors' => $errors
        ]);
    }

    public function setLoggingEnabled(bool $enabled): self
    {
        $this->loggingEnabled = $enabled;
        return $this;
    }

    /**
     * @param array<string,mixed> $data
     * @return void
     */
    private function writeLine(array $data): void
    {
        // Add timestamp to every message
        $data['timestamp'] = $this->getTimestamp();

        echo json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . PHP_EOL;
    }

    private function getTimestamp(): string
    {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        return $dt->format('c'); // ISO 8601 format
    }
}
