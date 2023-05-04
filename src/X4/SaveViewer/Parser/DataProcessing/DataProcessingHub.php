<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\DataProcessing;

use AppUtils\ClassHelper;
use AppUtils\FileHelper;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList;

class DataProcessingHub
{
    private Collections $collections;

    public function __construct(Collections $collections)
    {
        $this->collections = $collections;
    }

    public function getCollections() : Collections
    {
        return $this->collections;
    }

    public function process() : void
    {
        $ids = FileHelper::createFileFinder(__DIR__.'/Processors')
            ->getPHPClassNames();

        $classTemplate = str_replace(
            ClassHelper::getClassTypeName(KhaakStationsList::class),
            '{ID}',
            KhaakStationsList::class
        );

        foreach($ids as $id)
        {
            $class = str_replace('{ID}', $id, $classTemplate);

            ClassHelper::requireObjectInstanceOf(
                BaseDataProcessor::class,
                new $class($this)
            )
                ->process();
        }
    }
}
