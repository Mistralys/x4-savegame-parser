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
use function AppLocalize\t;
use function React\Async\await;

class X4Monitor extends BaseMonitor
{
    private bool $optionKeepXML = false;
    private bool $optionAutoBackup = true;
    private bool $optionLogging = false;

    public function optionLogging(bool $enabled) : self
    {
        $this->optionLogging = $enabled;
        $this->output->setLoggingEnabled($enabled);
        return $this;
    }

    public function optionAutoBackup(bool $enabled) : self
    {
        $this->optionAutoBackup = $enabled;
        return $this;
    }

    public function optionKeepXML(bool $keep) : self
    {
        $this->optionKeepXML = $keep;
        return $this;
    }

    protected function setup() : void
    {
        $this->notify('MONITOR_STARTED');

        $this->logHeader('X4 Savegame unpacker');
        $this->log('Updates are run every [%s].', ConvertHelper::time2string($this->getTickSize()));
        $this->log('Keep XML files: %s', strtoupper(ConvertHelper::bool2string($this->optionKeepXML, true)));
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

        $this->notify('SAVE_DETECTED', [
            'name' => $save->getSaveName(),
            'path' => $save->getSaveFile()->getPath()
        ]);

        if($save->hasData()) {
            $this->log('> Skipping, already parsed.');
            return;
        }

        $this->log('> Parsing.');

        $this->notify('SAVE_PARSING_STARTED', [
            'name' => $save->getSaveName()
        ]);

        await(new Promise(function(callable $resolve) use ($save)
        {
            $file = $save->getSaveFile();

            $this->log('...Unzipping.');
            $this->notify('SAVE_UNZIPPING');
            $file->unzip();

            $this->log('...Extracting and writing files.');
            $this->notify('SAVE_EXTRACTING');

            SaveParser::create($file)
                ->optionAutoBackup($this->optionAutoBackup)
                ->optionKeepXML($this->optionKeepXML)
                ->setLoggingEnabled($this->optionLogging)
                ->unpack();

            $this->log('...Done.');
            $this->notify('SAVE_PARSING_COMPLETE');
            $this->log('');

            $resolve();
        }));
    }
}
