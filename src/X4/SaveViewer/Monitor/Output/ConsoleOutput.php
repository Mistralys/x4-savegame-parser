<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor\Output;

use League\CLImate\CLImate;

class ConsoleOutput implements MonitorOutputInterface
{
    private CLImate $climate;
    private bool $loggingEnabled = false;

    public function __construct()
    {
        $this->climate = new CLImate();
    }

    public function start() : void
    {

    }

    public function log(string $message, ...$args): void
    {
        if(!$this->loggingEnabled) {
            return;
        }

        $this->climate->out(sprintf($message, ...$args));
    }

    public function logHeader(string $title, ...$args): void
    {
        // Headers are always shown, or do they follow logging enabled?
        // In BaseMonitor::logHeader calls log(), so they follow logging enabled.
        if(!$this->loggingEnabled) {
            return;
        }

        $this->climate->border('-');
        $this->climate->out(sprintf($title, ...$args));
        $this->climate->border('-');
    }

    public function tick(int $counter): void
    {
        // Ticks are noisy, only show if logging enabled (or maybe even stricter?)
        // BaseMonitor::handleTick calls _handleTick which might log things.
        // But the tick itself isn't necessarily logged in BaseMonitor outside of _handleTick implementation.
        // Wait, BaseMonitor.php doesn't log "tick" explicitly in handleTick().
        // It calls _handleTick().
        // If I look at X4Monitor.php (which I can't read fully yet, but I saw snippet),
        // let's assume tick() here is for the purpose of the JSON heartbeat.
        // For Console, we probably don't want to print "Tick 1", "Tick 2" forever unless debug is on.

        if ($this->loggingEnabled) {
            $this->climate->dim('Tick #' . $counter);
        }
    }

    public function notify(string $eventName, array $payload = []): void
    {
        // For console, we define a human-readable representation of events
        $message = sprintf('<bold>%s</bold>', $eventName);

        if(!empty($payload)) {
            $message .= ': ' . json_encode($payload, JSON_PRETTY_PRINT);
        }

        $this->climate->out($message);
    }

    public function setLoggingEnabled(bool $enabled): self
    {
        $this->loggingEnabled = $enabled;
        return $this;
    }
}
