<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor\Output;

interface MonitorOutputInterface
{
    /**
     * Log a message to the output.
     * This may be displayed or suppressed depending on configuration.
     *
     * @param string $message Sprintf-style format string.
     * @param mixed ...$args Sprintf args.
     */
    public function log(string $message, ...$args): void;

    /**
     * Log a header message (usually emphasized).
     *
     * @param string $title Sprintf-style format string.
     * @param mixed ...$args Sprintf args.
     */
    public function logHeader(string $title, ...$args): void;

    /**
     * Signal a heartbeat tick.
     *
     * @param int $counter
     */
    public function tick(int $counter): void;

    /**
     * Send a structured notification event.
     *
     * @param string $eventName Name of the event (e.g. SAVE_DETECTED)
     * @param array<string,mixed> $payload
     */
    public function notify(string $eventName, array $payload = []): void;

    /**
     * Send an error notification.
     *
     * @param \Throwable $e
     */
    public function error(\Throwable $e): void;

    /**
     * Set whether logging messages should be output.
     *
     * @param bool $enabled
     * @return $this
     */
    public function setLoggingEnabled(bool $enabled) : self;
}
