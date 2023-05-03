<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;

class Losses extends SubPage
{
    const URL_PARAM = 'Losses';

    public function getURLName() : string
    {
        return self::URL_PARAM;
    }

    public function isInSubnav() : bool
    {
        return true;
    }

    public function getTitle() : string
    {
        return 'Losses';
    }

    public function renderContent() : void
    {
        $log = $this->reader->getLog()->getDestroyed();

        $entries = $log->getEntries();

        usort($entries, function (LogEntry $a, LogEntry $b) {
            return $b->getTime() - $a->getTime();
        });

        ?>
        <h2>Ship and station losses</h2>
        <p>Ordered by most recent first.</p>
        <ul>
            <?php
            foreach ($entries as $entry)
            {
                ?>
                <li>
                    <b title="<?php echo ConvertHelper::interval2string($entry->getInterval()) ?>" style="cursor: help">
                        Hour <?php echo $entry->getHours() ?>
                    </b>
                    <?php echo $entry->getTitle().' '.$entry->getText() ?>
                </li>
                <?php
            }
            ?>
        </ul>
        <?php
    }
}
