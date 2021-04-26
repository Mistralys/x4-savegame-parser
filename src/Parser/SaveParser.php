<?php

declare(strict_types=1);

namespace Mistralys\X4Saves;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper;
use AppUtils\FileHelper_Exception;
use Mistralys\X4Saves\Data\SaveReader\Blueprints;
use SimpleXMLElement;

class SaveParser
{
    const ERROR_XML_FILE_NOT_FOUND = 84401;

    private string $saveName;

    private string $outputFolder;

    private string $zipFile;

    private string $xmlFile;

    private string $currentTag = '';

    private array $splitTags = array(
        'info',
        'factions',
        'blueprints',
        //'economylog',
        'log',
        'stats',
        'messages',
        'inventory'
    );

    /**
     * @var resource|NULL
     */
    private $outFile = null;

    /**
     * @var string[]
     */
    private array $openingTags;

    /**
     * @var string[]
     */
    private array $closingTags;

    private array $analysis = array(
        'date' => null,
        'parts' => array()
    );

    private string $analysisFile;

    private bool $analysisLoaded = false;

    public function __construct(string $saveName)
    {
        $this->saveName = $this->parseName($saveName);
        $this->zipFile = X4_SAVES_FOLDER.'/'.$saveName.'.xml.gz';
        $this->outputFolder = X4_SAVES_FOLDER.'/unpack_'.$this->saveName;
        $this->xmlFile = X4_SAVES_FOLDER.'/'.$saveName.'.xml';
        $this->analysisFile = $this->outputFolder.'/analysis.json';
    }

    /**
     * @throws X4Exception
     * @throws FileHelper_Exception
     */
    public function unpack() : void
    {
        $this->unpackArchive();
        $this->loadAnalysis();
        $this->splitFile();
    }

    public function isUnpacked() : bool
    {
        $this->loadAnalysis();

        return isset($this->analysis['date']);
    }

    private function loadAnalysis() : void
    {
        if($this->analysisLoaded) {
            return;
        }

        $this->log('Loading previous analysis.');

        $this->analysisLoaded = true;

        if(!file_exists($this->analysisFile)) {
            $this->log('No analysis found.');
            return;
        }

        $data = FileHelper::parseJSONFile($this->analysisFile);
        if(filemtime($this->xmlFile) === $data['date'])
        {
            $this->log('Found an up to date analysis.');
            $this->analysis = $data;
            return;
        }

        $this->log('Found an older existing analysis; Removing it.');
        FileHelper::deleteTree($this->outputFolder);
    }

    public function convert() : void
    {
        foreach($this->analysis['parts'] as $tagName => $def)
        {
            $this->log(sprintf('Processing part [%s].', $tagName));
            $this->log(sprintf('Found [%s] files to process.', count($def['xmlFiles'])));

            foreach($def['xmlFiles'] as $file) {
                $path = $this->outputFolder . '/' . $file;
                $element = simplexml_load_string(FileHelper::readContents($path));

                $method = 'unpack_' . $tagName;
                $this->$method($element);
            }
        }
    }

    private function log(string $message) : void
    {
        echo $message.PHP_EOL;
    }

    /**
     * @throws X4Exception
     */
    private function unpackArchive() : void
    {
        if(file_exists($this->zipFile)) {
            $this->unzipArchive();
        }

        if(file_exists($this->xmlFile)) {
            return;
        }

        throw new X4Exception(
            sprintf('XML file [%s] not found.', basename($this->xmlFile)),
            sprintf(
                'The expected XML file not found at [%s].',
                $this->xmlFile
            ),
            self::ERROR_XML_FILE_NOT_FOUND
        );
    }

    private function unzipArchive() : void
    {
        //$helper = new ZIPHelper($this->zipFile);
        //$helper->extractAll(PARSER_SAVES_FOLDER);
    }

    private function splitFile() : void
    {
        $this->log(sprintf('Splitting the XML file [%s.].', $this->saveName));

        if($this->isUnpacked()) {
            $this->log('Already split, skipping.');
            return;
        }

        $this->log('Extracting data.');

        $this->createFolders();
        $this->compileTags();

        $this->analysis['date'] = filemtime($this->xmlFile);

        $in = fopen($this->xmlFile, "r+");
        $number = 0;

        while (($line = stream_get_line($in, 1024 * 1024, "\n")) !== false)
        {
            $this->analyzeLine(trim($line), $number);
            $number++;
        }

        fclose($in);

        FileHelper::saveAsJSON($this->analysis, $this->analysisFile, true);

        $this->log('Done extracting.');
    }

    private function createFolders() : void
    {
        FileHelper::createFolder($this->outputFolder);
        FileHelper::createFolder($this->outputFolder.'/xml');
        FileHelper::createFolder($this->outputFolder.'/json');
    }

    private function compileTags() : void
    {
        foreach($this->splitTags as $tagName)
        {
            $this->openingTags['<'.$tagName.'>'] = $tagName;
            $this->closingTags['</'.$tagName.'>'] = $tagName;
        }
    }

    private function analyzeLine(string $line, int $number) : void
    {
        if(isset($this->openingTags[$line]))
        {
            $tagName = $this->openingTags[$line];

            if(!isset($this->analysis['parts'][$tagName])) {
                $this->analysis['parts'][$tagName] = array(
                    'startAt' => $number,
                    'instances' => 0,
                    'xmlFiles' => array(),
                    'endAt' => 0
                );
            }

            $this->analysis['parts'][$tagName]['instances']++;

            $this->log(sprintf('...Found opening tag [%s].', $tagName));

            $outFile = sprintf(
                '%s/xml/%s-%s.xml',
                $this->outputFolder,
                $tagName,
                $this->analysis['parts'][$tagName]['instances']
            );

            $this->analysis['parts'][$tagName]['xmlFiles'][] = 'xml/'.basename($outFile);

            $this->currentTag = $tagName;
            $this->outFile = fopen($outFile, 'w+');
        }

        // We're not currently capturing the content for any known tags.
        if($this->currentTag === '') {
            return;
        }

        if(isset($this->outFile)) {
            fwrite($this->outFile, $line . PHP_EOL);
        }

        // Found a closing tag, cleaning up any open file.
        if(isset($this->closingTags[$line]))
        {
            $tagName = $this->closingTags[$line];

            $this->currentTag = '';

            if(isset($this->outFile)) {
                fclose($this->outFile);
                $this->outFile = null;
            }

            if(isset($this->analysis['parts'][$tagName])) {
                $this->log(sprintf('...Closing tag [%s].', $tagName));
                $this->analysis['parts'][$tagName]['endAt'] = $number;
            }
        }
    }

    private function unpack_stats(SimpleXMLElement $node) : void
    {
        $stats = array();
        $found = false;

        foreach($node->stat as $stat)
        {
            $name = (string)$stat['id'];
            $stats[$name] = floatval($stat['value']);

            if($name === 'time_total') {
                $found = true;
            }
        }

        if($found) {
            $this->saveData('statistics', $stats);
        }
    }

    protected array $terms = array(
        'emergency alert' => 'emergency',
        'reputation gained' => 'reputation',
        'reputation lost' => 'reputation',
        'promotion' => 'promotion',
        'discount' => 'discount',
        'rewarded' => 'reward',
        'task complete' => '',
        'police interdiction' => '',
        'found lockbox' => 'lockbox',
        'pirate harassment' => 'pirates',
        'ship resupplied' => 'ship-supply',
        'finished repairing' => 'ship-supply',
        'finished construction' => 'ship-construction',
        'assigned individual' => 'crew-assignment',
        'forced to flee' => 'attacked',
        'is under attack' => 'attacked',
        'was destroyed' => 'destroyed',
        'station completed' => 'station-building',
        'station under construction' => 'station-building',
        'has dropped to' => 'station-finance',
        'received surplus' => 'station-finance',
        'mounting defence' => 'war',
        'reconnaissance in' => 'war',
        'trade completed' => 'trade',
        'war update' => 'war',
    );

    private function unpack_log(SimpleXMLElement $node) : void
    {
        $data = array();
        foreach ($node->entry as $entry)
        {
            $category = (string)$entry['category'];
            if(empty($category)) {
                $category = 'event';
            }

            $title = (string)$entry['title'];
            $text = (string)$entry['text'];

            $fileCategory = $category;
            foreach($this->terms as $term => $termCategory) {
                if (stristr($title, $term) !== false || stristr($text, $term) !== false) {
                    $fileCategory = $termCategory;
                    break;
                }
            }

            if(empty($fileCategory)) {
                continue;
            }

            if(!isset($data[$fileCategory])) {
                $data[$fileCategory] = array();
            }

            $data[$fileCategory][] = array(
                'category' => $category,
                'time' => floatval($entry['time']),
                'title' => $title,
                'text' => $text,
                'faction' => (string)$entry['faction']
            );
        }

        foreach($data as $fileCategory => $entries) {
            if(!empty($entries)) {
                $this->saveData('log/'.$fileCategory, $entries);
            }
        }
    }

    private function unpack_messages(SimpleXMLElement $node) : void
    {
        $messages = array();

        foreach($node->entry as $entry) {
            $messages[] = array(
                'id' => intval($entry['id']),
                'time' => floatval($entry['time']),
                'title' => (string)$entry['title'],
                'source' => (string)$entry['source']
            );
        }

        $this->saveData('messages', $messages);
    }

    private function unpack_factions(SimpleXMLElement $node) : void
    {
        $factions = array();
        foreach($node->faction as $factionNode)
        {
            $id = (string)$factionNode['id'];
            if(strstr($id, 'visitor') !== false) {
                continue;
            }

            $faction = array(
                'id' => $id,
                'active' => true,
                'relationsLocked' => false,
                'relations' => array(),
                'relationBoosters' => array(),
                'moods' => array(),
                'licenses' => array(),
                'discounts' => array(),
                'signals' => array()
            );

            if((string)$factionNode['active'] === '0') {
                $faction['active'] = false;
            }

            if((string)$factionNode->relations['locked'] === '1') {
                $faction['relationsLocked'] = true;
            }

            foreach($factionNode->relations->relation as $relation) {
                $relID = (string)$relation['faction'];

                if(strstr($relID, 'visitor') !== false) {
                    continue;
                }

                $faction['relations'][$relID] = (float)$relation['relation'];
            }

            ksort($faction['relations']);

            foreach($factionNode->relations->booster as $booster) {
                $faction['relationBoosters'][(string)$booster['faction']] = array(
                    'value' => floatval($booster['relation']),
                    'time' => floatval($booster['time'])
                );
            }

            if(isset($factionNode->moods)) {
                foreach ($factionNode->moods->mood as $mood) {
                    $faction['moods'][(string)$mood['type']] = (string)$mood['level'];
                }
            }

            if(isset($factionNode->licences)) {
                foreach($factionNode->licences->licence as $license) {
                    $faction['licenses'][(string)$license['type']] = ConvertHelper::explodeTrim(
                        " ",
                        (string)$license['factions']
                    );
                }
            }

            if(isset($factionNode->discounts)) {
                foreach($factionNode->discounts->booster as $discount) {
                    $faction['discounts'][(string)$discount['faction']] = floatval($discount['amount']);
                }
            }

            if(isset($factionNode->signals)) {
                foreach($factionNode->signals->response as $signal) {
                    $faction['signals'][(string)$signal['signal']] = (string)$signal['response'];
                }
            }

            $factions[$id] = $faction;
        }

        $this->saveData('factions', $factions);
    }

    private function unpack_inventory(SimpleXMLElement $node) : void
    {
        $data = array();
        $found = false;
        
        foreach($node->ware as $ware)
        {
            $name = (string)$ware['ware'];
            $data[$name] = (int)$ware['amount'];
            
            if($name === 'weapon_gen_spacesuit_repairlaser_01_mk1') {
                $found = true;
            }
        }

        if($found) {
            $this->saveData('inventory', $data);
        }
    }

    private function unpack_blueprints(SimpleXMLElement $node) : void
    {
        $partDefs = array(
            'turret' => Blueprints::CATEGORY_TURRETS,
            'ship' => Blueprints::CATEGORY_SHIPS,
            'shield' => Blueprints::CATEGORY_SHIELDS,
            'module' => Blueprints::CATEGORY_MODULES,
            'engine' => Blueprints::CATEGORY_ENGINES,
            'mod' => Blueprints::CATEGORY_MODIFICATIONS,
            'weapon' => Blueprints::CATEGORY_WEAPONS,
            'satellite' => Blueprints::CATEGORY_DEPLOYABLES,
            'resourceprobe' => Blueprints::CATEGORY_DEPLOYABLES,
            'waypointmarker' => Blueprints::CATEGORY_DEPLOYABLES,
            'survey' => Blueprints::CATEGORY_DEPLOYABLES,
            'paintmod' => Blueprints::CATEGORY_SKINS,
            'clothingmod' => Blueprints::CATEGORY_SKINS,
            'countermeasure' => Blueprints::CATEGORY_COUNTERMEASURES,
            'missile' => Blueprints::CATEGORY_MISSILES,
            'thruster' => Blueprints::CATEGORY_THRUSTERS
        );

        $data = array();

        foreach($node->blueprint as $blueprint)
        {
            $id = (string)$blueprint['ware'];
            $parts = explode('_', $id);
            $type = array_shift($parts);
            $category = Blueprints::CATEGORY_UNKNOWN;

            if(isset($partDefs[$type])) {
                $category = $partDefs[$type];
            }

            if(!isset($data[$category])) {
                $data[$category] = array();
            }

            $data[$category][] = $id;
        }

        $this->saveData('blueprints', $data);
    }

    private function unpack_info(SimpleXMLElement $node) : void
    {
        $data = array(
            'saveDate' => (int)$node->save['date'],
            'saveName' => (string)$node->save['name'],
            'playerName' => (string)$node->player['name'],
            'money' => (int)$node->player['money']
        );

        $this->saveData('player', $data);
    }

    private function saveData(string $nodeName, array $data) : void
    {
        FileHelper::saveAsJSON(
            $data,
            $this->outputFolder.'/json/'.$nodeName.'.json',
            true
        );
    }

    private function parseName(string $fileName) : string
    {
        if(!strstr($fileName, '.')) {
            return $fileName;
        }

        $parts = explode('.', $fileName);
        return array_shift($parts);
    }
}
