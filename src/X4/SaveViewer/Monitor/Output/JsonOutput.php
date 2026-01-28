<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor\Output;

class JsonOutput implements MonitorOutputInterface
{
    private bool $loggingEnabled = false;

    public function log(string $message, ...$args): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $this->writeLine([
            'type' => 'log',
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
        $this->writeLine([
            'type' => 'event',
            'name' => $eventName,
            'payload' => $payload
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
        echo json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . PHP_EOL;
    }
}
