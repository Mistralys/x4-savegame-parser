<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper_Exception;
use AppUtils\StringBuilder;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveParser;
use Mistralys\X4\SaveViewer\SaveViewerException;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Promise\Promise;
use React\Socket\SocketServer;
use function React\Async\await;
use Mistralys\X4\SaveViewer\Config\Config;

abstract class BaseMonitor
{
    public const ERROR_NOT_COMMAND_LINE = 136301;
    public const ERROR_CANNOT_START_LOOP = 136302;

    /**
     * The amount of seconds between updates.
     * @var int
     */
    private int $tickSize = 5;

    private int $tickCounter = 1;

    protected SaveManager $manager;
    protected LoopInterface $loop;

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
        echo sprintf(...$args).PHP_EOL;
    }

    protected function logHeader(...$args) : void
    {
        $this->log('--------------------------------------------');
        $this->log(...$args);
        $this->log('--------------------------------------------');
    }

    public function handleTick() : void
    {
        $this->tickCounter++;

        $this->_handleTick();
    }

    abstract protected function _handleTick() : void;
}
