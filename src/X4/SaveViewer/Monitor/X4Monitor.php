<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Monitor;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper_Exception;
use AppUtils\StringBuilder;
use Mistralys\X4\SaveViewer\CLI\ExtractionQueue;
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
    private ?string $lastDetectedSavePath = null;
    private int $cleanupCounter = 0;

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

        // Check extraction queue first
        $queue = new ExtractionQueue($this->manager);

        if ($queue->hasItems()) {
            $this->processQueuedSave($queue);
            return;
        }

        // Fall back to current behavior (most recent save)
        $this->processCurrentSave();

        // Periodic cache cleanup every 60 ticks (~5 minutes) - WP5
        $this->cleanupCounter++;
        if ($this->cleanupCounter >= 60) {
            $this->cleanupCounter = 0;
            $this->performCacheCleanup();
        }
    }

    private function processQueuedSave(ExtractionQueue $queue): void
    {
        $saveId = $queue->peek();
        $this->log('Processing queued save [%s]...', $saveId);

        // Validate save exists
        try {
            $save = $this->manager->idExists($saveId)
                ? $this->manager->getByID($saveId)
                : $this->manager->getSaveByName($saveId);
        } catch (\Throwable $e) {
            // Save not found - remove from queue
            $queue->pop();
            $this->log('> Queued save not found, removing from queue.');
            $this->log('');
            return;
        }

        // Check if already extracted
        if ($save->hasData()) {
            $queue->pop(); // Remove from queue
            $this->log('> Already extracted, skipping.');
            $this->log('');
            return;
        }

        // Extract the save
        $this->notify('SAVE_DETECTED', [
            'name' => $save->getSaveName(),
            'path' => $save->getSaveFile()->getReferenceFile()->getPath(),
            'source' => 'queue'
        ]);

        $this->notify('SAVE_PARSING_STARTED', [
            'name' => $save->getSaveName()
        ]);

        await(new Promise(function(callable $resolve, callable $reject) use ($save, $queue)
        {
            try {
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
                $this->notify('SAVE_PARSING_COMPLETE', [
                    'saveName' => $save->getSaveName(),
                    'extractionDuration' => $save->getAnalysis()->getExtractionDuration(),
                    'extractionDurationFormatted' => $save->getAnalysis()->getExtractionDurationFormatted()
                ]);
                $this->log('');

                // Remove from queue after successful extraction
                $queue->pop();

                $resolve(null);
            } catch (\Throwable $e) {
                $this->notifyError($e);
                // Don't remove from queue on error - will retry next tick
                $reject($e);
            }
        }));
    }

    private function processCurrentSave(): void
    {
        $save = $this->manager->getCurrentSave();

        if($save === null) {
            $this->log('No current savegame found.');
            return;
        }

        $this->log('Latest savegame is [%s].', $save->getSaveName());

        $currentPath = $save->getSaveFile()->getReferenceFile()->getPath();

        if($save->hasData()) {
            $this->log('> Skipping, already parsed.');
            return;
        }

        // Only emit SAVE_DETECTED event when a new save file is detected that needs processing
        if ($this->lastDetectedSavePath !== $currentPath) {
            $this->lastDetectedSavePath = $currentPath;
            $this->notify('SAVE_DETECTED', [
                'name' => $save->getSaveName(),
                'path' => $currentPath,
                'source' => 'monitor'
            ]);
        }


        $this->log('> Parsing.');

        $this->notify('SAVE_PARSING_STARTED', [
            'name' => $save->getSaveName()
        ]);

        await(new Promise(function(callable $resolve, callable $reject) use ($save)
        {
            try {
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
                $this->notify('SAVE_PARSING_COMPLETE', [
                    'saveName' => $save->getSaveName(),
                    'extractionDuration' => $save->getAnalysis()->getExtractionDuration(),
                    'extractionDurationFormatted' => $save->getAnalysis()->getExtractionDurationFormatted()
                ]);
                $this->log('');

                $resolve(null);
            } catch (\Throwable $e) {
                $this->notifyError($e);
                $reject($e);
            }
        }));
    }

    /**
     * Perform periodic cache cleanup to remove obsolete cache files.
     * Removes cache directories for saves that no longer exist.
     *
     * @return void
     */
    private function performCacheCleanup() : void
    {
        try {
            $cache = new \Mistralys\X4\SaveViewer\CLI\QueryCache($this->manager);
            $removed = $cache->cleanupObsoleteCaches();

            if ($removed > 0) {
                $this->log('Cache cleanup: Removed %d obsolete cache director%s',
                    $removed,
                    $removed === 1 ? 'y' : 'ies'
                );
            }
        } catch (\Throwable $e) {
            $this->log('Warning: Cache cleanup failed: %s', $e->getMessage());
        }
    }
}
