<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor;

use AppUtils\FileHelper_Exception;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveViewerException;
use Mistralys\X4\SaveViewer\Monitor\Output\ConsoleOutput;
use Mistralys\X4\SaveViewer\Monitor\Output\MonitorOutputInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Mistralys\X4\SaveViewer\Config\Config;

abstract class BaseMonitor
{
    public const int ERROR_NOT_COMMAND_LINE = 136301;
    public const int ERROR_CANNOT_START_LOOP = 136302;

    public const string ARG_JSON_OUTPUT = '--json';

    /**
     * The amount of seconds between updates.
     * @var int
     */
    private int $tickSize = 5;

    private int $tickCounter = 1;

    protected SaveManager $manager;
    protected LoopInterface $loop;
    protected MonitorOutputInterface $output;

    /**
     * @throws SaveViewerException
     * @throws FileHelper_Exception
     * @see X4Server::ERROR_NOT_COMMAND_LINE
     */
    public function __construct()
    {
        $this->requireCLI();

        $this->manager = new SaveManager(SaveSelector::create(
            Config::getSavesFolder(),
            Config::getStorageFolder()
        ));

        $this->output = new ConsoleOutput();
    }

    public function setOutput(MonitorOutputInterface $output) : self
    {
        $this->output = $output;
        return $this;
    }

    public function getTickCounter() : int
    {
        return $this->tickCounter;
    }

    public function getTickSize() : int
    {
        return $this->tickSize;
    }

    private function isCLI() : bool
    {
        return PHP_SAPI === "cli";
    }

    /**
     * @throws SaveViewerException
     */
    private function requireCLI() : void
    {
        if($this->isCLI()) {
            return;
        }

        throw new SaveViewerException(
            'The server can only be run from the command line.',
            '',
            self::ERROR_NOT_COMMAND_LINE
        );
    }

    /**
     * Start the server listening.
     */
    public function start() : void
    {
        $loop = Loop::get();
        if($loop === null) {
            throw new SaveViewerException(
                'Cannot create server loop.',
                '',
                self::ERROR_CANNOT_START_LOOP
            );
        }

        $this->loop = $loop;

        $this->loop->addPeriodicTimer($this->tickSize, array($this, 'handleTick'));

        $this->setup();

        $this->loop->run();
    }

    abstract protected function setup() : void;

    protected function log(...$args) : void
    {
        $this->output->log(...$args);
    }

    protected function logHeader(...$args) : void
    {
        $this->output->logHeader(...$args);
    }

    protected function notify(string $eventName, array $payload = []) : void
    {
        $this->output->notify($eventName, $payload);
    }

    protected function notifyError(\Throwable $e) : void
    {
        $this->output->error($e);
    }

    public function handleTick() : void
    {
        $this->tickCounter++;
        $this->output->tick($this->tickCounter);

        try {
            $this->_handleTick();
        } catch (\Throwable $e) {
            $this->notifyError($e);
            $this->loop->stop();
            exit(1);
        }
    }

    abstract protected function _handleTick() : void;
}
