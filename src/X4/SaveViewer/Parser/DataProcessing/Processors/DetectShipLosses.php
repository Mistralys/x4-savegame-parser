<?php
/**
 * @package X4SaveViewer
 * @subpackage Parser
 * @see \Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\DetectShipLosses
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors;

use AppUtils\ConvertHelper;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\BaseDataProcessor;
use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;

/**
 * Goes through the event log, and extracts all player ships
 * that have been destroyed, with all relevant details (as
 * far as they are available).
 *
 * @package X4SaveViewer
 * @subpackage Parser
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class DetectShipLosses extends BaseDataProcessor
{
    public const FILE_ID = 'losses';
    public const KEY_TIME = 'time';
    public const KEY_SHIP_NAME = 'shipName';
    public const KEY_SHIP_CODE = 'shipCode';
    public const KEY_LOCATION = 'location';
    public const KEY_COMMANDER = 'commander';
    public const KEY_DESTROYED_BY = 'destroyedBy';

    /**
     * @var array<int,array{time:string,shipName:string,shipCode:string,location:string,commander:string,destroyedBy:string}>
     */
    private array $data = array();

    protected function _process() : void
    {
        $entries = $this->collections->eventLog()->getEntries();

        foreach($entries as $entry)
        {
            if($entry->getCategory() !== 'upkeep') {
                continue;
            }

            if(stripos($entry->getTitle(), 'destroyed')) {
                $this->registerLoss($entry);
            }
        }

        $this->saveAsJSON($this->data, self::FILE_ID);
    }

    public function registerLoss(LogEntryType $entry) : void
    {
        $info = self::parseEntry($entry);

        if($info !== null) {
            $this->data[] = $info;
        }
    }

    /**
     * @param LogEntryType $entry
     * @return array<int,array{time:string,shipName:string,shipCode:string,location:string,commander:string,destroyedBy:string}>|null
     */
    public static function parseEntry(LogEntryType $entry) : ?array
    {
        $info = self::parseTitle($entry->getTitle());

        if($info === null) {
            return null;
        }

        $details = self::parseText($entry->getText());

        return array(
            self::KEY_TIME => $entry->getTime(),
            self::KEY_SHIP_NAME => $info['shipName'],
            self::KEY_SHIP_CODE => $info['shipCode'],
            self::KEY_LOCATION => $details['location'],
            self::KEY_COMMANDER => $details['commander'],
            self::KEY_DESTROYED_BY => $details['destroyed by']
        );
    }

    /**
     * @param string $title
     * @return array{shipName:string,shipCode:string}|null
     */
    public static function parseTitle(string $title) : ?array
    {
        preg_match('/(.*)\(([A-Z]{3}-[0-9]+)\) was destroyed\./', $title, $matches);

        if(!empty($matches[0]))
        {
            return array(
                'shipName' => trim($matches[1]),
                'shipCode' => $matches[2]
            );
        }

        return null;
    }

    /**
     * @param string $text
     * @return array{location:string,commander:string,"destroyed by":string}
     */
    public static function parseText(string $text) : array
    {
        $lines = ConvertHelper::explodeTrim('[\012]', $text);

        $attributes = array(
            'location',
            'commander',
            'destroyed by'
        );

        $result = array(
            'location' => '',
            'commander' => '',
            'destroyed by' => ''
        );

        foreach($lines as $line)
        {
            foreach($attributes as $attribute)
            {
                if(stripos($line, $attribute) === 0)
                {
                    $parts = ConvertHelper::explodeTrim(':', $line);
                    array_shift($parts);
                    $result[$attribute] = implode(':', $parts);
                    break;
                }
            }
        }

        return $result;
    }
}
