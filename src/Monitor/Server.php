<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Monitor;

use AppUtils\StringBuilder;
use Mistralys\X4Saves\Data\SaveManager;
use Mistralys\X4Saves\X4Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Message\Response;
use React\Http\Server;

class X4Server
{
    const ERROR_NOT_COMMAND_LINE = 85201;

    /**
     * The amount of minutes between updates.
     * @var int
     */
    private int $tick = 1;

    private int $tickCounter = 1;

    private SaveManager $manager;

    /**
     * @throws X4Exception
     * @see X4Server::ERROR_NOT_COMMAND_LINE
     */
    public function __construct()
    {
        $this->requireCLI();

        $this->manager = new SaveManager();
    }

    private function isCLI() : bool
    {
        return php_sapi_name() === "cli";
    }

    /**
     * @throws X4Exception
     */
    private function requireCLI() : void
    {
        if($this->isCLI()) {
            return;
        }

        throw new X4Exception(
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
        $loop = Factory::create();
        $loop->addPeriodicTimer($this->tick * 60, array($this, 'handleTick'));

        $server = new Server($loop, array($this, 'handleRequest'));

        $socket = new \React\Socket\Server('127.0.0.1:9494', $loop);
        $server->listen($socket);

        $this->logHeader('X4 Savegame server');
        $this->log('Listening on [%s].', str_replace('tcp:', 'http:', $socket->getAddress()));
        $this->log('Updates are run every [%s] minutes.', $this->tick);
        $this->log('');

        $loop->run();
    }

    public function handleRequest(ServerRequestInterface $request) : Response
    {
        $delay = ($this->tick * 60) + 30;

        return new Response(
            200,
            array(
                'Content-Type' => 'text/html'
            ),
            $this->renderSummary().
            '<script>setTimeout(function() {document.location.reload()}, '.($delay * 1000).')</script>'
        );
    }

    private function renderSummary() : string
    {
        $text = (new StringBuilder())
            ->add('Server tick:')
            ->add((string)$this->tickCounter)
            ->add('@')
            ->time()
            ->para();

        $currentSave = $this->manager->getCurrentSave();

        if(!$currentSave) {
            return (string)$text->bold('No savegames found.');
        }

        if(!$currentSave->isDataValid()) {
            return (string)$text
                ->bold('Savegame is not finished unpacking.')->nl()
                ->add('Please wait until the next server tick for it to be updated.');
        }

        $reader = $currentSave->getReader();

        $destroyed = $reader->getLog()->getDestroyed();

        $text
            ->add('Amount of savegames:')->add($this->manager->countSaves())->nl()
            ->add('Latest savegame:')->add($currentSave->getName())->nl()
            ->add('Losses:')->add($destroyed->countEntries())->nl()
            ->add('Last 5 losses:')->nl();

        $entries = $destroyed->getEntries();

        for($i=0; $i < 5; $i++) {
            if(empty($entries)) {
                break;
            }

            $loss = array_pop($entries);
            $text->add('-')->bold($loss->getHours())->add($loss->getTitle())->add($loss->getText())->nl();
        }

        return (string)$text;
    }

    private function log(...$args) : void
    {
        echo sprintf(...$args).PHP_EOL;
    }

    private function logHeader(...$args) : void
    {
        $this->log('--------------------------------------------');
        $this->log(...$args);
        $this->log('--------------------------------------------');
    }

    public function handleTick() : void
    {
        $this->tickCounter++;

        $this->logHeader('Handling tick [%s]', $this->tickCounter);

        $this->update();

        $this->log('');
    }

    private function update()
    {
        $saves = $this->manager->getSaves();

        foreach ($saves as $save)
        {
            if($save->isDataValid()) {
                $this->log('The save [%s] is already up to date.', $save->getName());
                continue;
            }

            $this->log('Parsing the save file [%s].', $save->getName());
            $save->unpackAndConvert();
        }
    }
}
