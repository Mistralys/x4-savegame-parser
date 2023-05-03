<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer;

use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\Fragment\ClusterConnectionFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\EventLogFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\FactionsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\PlayerStatsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\SaveInfoFragment;

class SaveParser extends BaseXMLParser
{
    private string $saveName;
    private string $outputFolder;
    private string $xmlFile;

    public function __construct(string $saveName)
    {
        $this->saveName = $this->parseName($saveName);

        $this->setSaveGameFolder(X4_SAVES_FOLDER);

        parent::__construct(new Collections($this->outputFolder), $this->xmlFile, $this->outputFolder);
    }

    public function getCollections() : Collections
    {
        return $this->collections;
    }

    public function unpack() : void
    {
        $this->processFile();
        $this->postProcessFragments();
    }

    protected function registerActions() : void
    {
        $this->registerExtractXML('savegame.info', SaveInfoFragment::class);
        $this->registerExtractXML('savegame.universe.factions', FactionsFragment::class);
        $this->registerExtractXML('savegame.universe.component[galaxy].connections.connection[ID]', ClusterConnectionFragment::class);
        $this->registerExtractXML('savegame.stats', PlayerStatsFragment::class);
        $this->registerExtractXML('savegame.log', EventLogFragment::class);

        $this->registerIgnore('savegame.messages');
        $this->registerIgnore('savegame.universe.blacklists');
        $this->registerIgnore('savegame.universe.traderules');
        $this->registerIgnore('savegame.universe.jobs');
        $this->registerIgnore('savegame.universe.god');
        $this->registerIgnore('savegame.universe.controltextures');
        $this->registerIgnore('savegame.universe.physics');
        $this->registerIgnore('savegame.economylog');
        $this->registerIgnore('savegame.script');
        $this->registerIgnore('savegame.md');
        $this->registerIgnore('savegame.missions');
        $this->registerIgnore('savegame.aidirector');
        $this->registerIgnore('savegame.operations');
        $this->registerIgnore('savegame.ventures');
        $this->registerIgnore('savegame.notifications');
        $this->registerIgnore('savegame.ui');
        $this->registerIgnore('savegame.universe.uianchorhelper');
        $this->registerIgnore('savegame.universe.cameraanchor');
    }

    public function setSaveGameFolder(string $folderPath) : self
    {
        $folder = $folderPath;

        $this->outputFolder = $folder .'/unpack_'.$this->saveName;
        $this->xmlFile = $folder .'/'.$this->saveName.'.xml';

        return $this;
    }

    private function parseName(string $fileName) : string
    {
        $fileName = basename($fileName);

        if(strpos($fileName, '.') === false) {
            return $fileName;
        }

        $parts = explode('.', $fileName);

        array_pop($parts);

        return implode('.', $parts);
    }
}
