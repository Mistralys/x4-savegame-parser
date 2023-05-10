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
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Promise\Promise;
use React\Socket\SocketServer;
use function React\Async\await;

class X4Monitor extends BaseMonitor
{
    protected function setup() : void
    {
        $this->logHeader('X4 Savegame unpacker');
        $this->log('Updates are run every [%s].', ConvertHelper::time2string($this->getTickSize()));
        $this->log('');
    }

    protected function _handleTick() : void
    {
        $this->logHeader('Handling tick [%s]', $this->getTickCounter());

        $save = $this->manager->getCurrentSave();

        if($save === null) {
            $this->log('No current savegame found.');
            return;
        }

        $this->log('Latest savegame is [%s].', $save->getSaveName());

        if($save->hasData()) {
            $this->log('> Skipping, already parsed.');
            return;
        }

        $this->log('> Parsing.');

        await(new Promise(function(callable $resolve) use ($save)
        {
            $file = $save->getSaveFile();

            $this->log('...Unzipping.');
            $file->unzip();

            $this->log('...Extracting and writing files.');

            SaveParser::create($file)
                ->setAutoBackupEnabled()
                ->unpack();

            $this->log('...Done.');
            $this->log('');

            $resolve();
        }));
    }
}
