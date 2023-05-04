<?php
/**
 * @package X4SaveViewer
 * @subpackage Parser
 * @see \Mistralys\X4\SaveViewer\SaveParser
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer;

use AppUtils\FileHelper\FileInfo;
use DateTime;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\Fragment\ClusterConnectionFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\EventLogFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\FactionsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\PlayerStatsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\SaveInfoFragment;

/**
 * Main parser class that dispatches the extraction of the
 * game data from an XML savegame to subclasses specialized
 * for the different XML structures.
 *
 * See the <code>Fragments</code> subfolder for the XML
 * node reading classes.
 *
 * @package X4SaveViewer
 * @subpackage Parser
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class SaveParser extends BaseXMLParser
{
    /**
     * @param FileInfo $saveFile Path to the XML save file to parse.
     * @param string $outputFolder Path the folder in which the savegame's data will be extracted into a subfolder.
     * @param DateTime|null $modTime Modification time of the save. Used when the
     *         savegame was zipped, to use the time the archive file was modified
     *         instead of the extracted XML file. Default is to use the modification
     *         time of the specified file.
     */
    public function __construct(FileInfo $saveFile, string $outputFolder, ?DateTime $modTime=null)
    {
        if($modTime === null) {
            $modTime = $saveFile->getModifiedDate();
        }

        $saveFolder = sprintf(
            '%s/unpack-%s-%s',
            $outputFolder,
            $modTime->format('Ymdhis'),
            $saveFile->getBaseName()
        );

        parent::__construct(
            new Collections($saveFolder.'/JSON'),
            $saveFile->getPath(),
            $saveFolder
        );
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
}
