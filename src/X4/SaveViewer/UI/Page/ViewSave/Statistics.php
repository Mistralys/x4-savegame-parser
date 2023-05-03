<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

class Statistics extends SubPage
{
    const URL_PARAM = 'Statistics';

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
        return 'Statistics';
    }

    public function renderContent() : void
    {
        $stats = $this->reader->getStatistics()->getStats();

        ?>
            <table class="table">
                <tbody>
                <?php
                foreach($stats as $name => $value) {
                    ?>
                    <tr>
                        <td class="align-right"><?php echo $value ?></td>
                        <th><?php echo $name ?></th>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        <?php
    }
}
