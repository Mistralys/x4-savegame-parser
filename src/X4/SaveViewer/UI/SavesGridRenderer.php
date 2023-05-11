<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI;

use AppUtils\Interfaces\RenderableInterface;
use AppUtils\Traits\RenderableTrait;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Icon;
use Mistralys\X4\UI\UserInterface;
use Mistralys\X4\UserInterface\DataGrid\DataGrid;
use Mistralys\X4\UserInterface\DataGrid\GridColumn;
use function AppLocalize\t;
use function AppUtils\sb;

class SavesGridRenderer implements RenderableInterface
{
    use RenderableTrait;

    public const COL_SAVE_TYPE = 'save-type';

    /**
     * @var BaseSaveFile[]
     */
    private array $saves;
    private DataGrid $grid;
    private GridColumn $cName;
    private GridColumn $cChar;
    private GridColumn $cMoney;
    private GridColumn $cModified;
    private GridColumn $cBackup;
    private GridColumn $cActions;
    private GridColumn $cType;

    /**
     * @var array<string,bool>
     */
    private array $enabled = array();

    public function __construct(UserInterface $ui, array $saves)
    {
        $this->saves = $saves;

        $this->grid = $ui->createDataGrid();

        $this->cName = $this->grid->addColumn('name', t('Name'));

        if($this->isColEnabled(self::COL_SAVE_TYPE))
        {
            $this->cType = $this->grid->addColumn(self::COL_SAVE_TYPE, t('Type'));
        }

        $this->cChar = $this->grid->addColumn('character', t('Character'));
        $this->cMoney = $this->grid->addColumn('money', t('Money'))
            ->alignRight();
        $this->cModified = $this->grid->addColumn('modified', t('Modified'));
        $this->cBackup = $this->grid->addColumn('backup', t('Backup?'))
            ->alignCenter();
        $this->cActions = $this->grid->addColumn('actions', t('Actions'))
            ->alignRight();
    }

    public function setColumnEnabled(string $name, bool $enabled) : self
    {
        $this->enabled[$name] = $enabled;
        return $this;
    }

    public function isColEnabled(string $name) : bool
    {
        return !(isset($this->enabled[$name]) && $this->enabled[$name] === false);
    }

    public function render() : string
    {
        foreach($this->saves as $save)
        {
            $row = $this->grid->createRow();
            $this->grid->addRow($row);

            if($save->isUnpacked())
            {
                $reader = $save->getDataReader();
                $saveInfo = $reader->getSaveInfo();
                $date = $saveInfo->getSaveDate();

                $row->setValue($this->cName, sb()->link($save->getSaveName(), $save->getURLView())->add('-')->add($saveInfo->getSaveName()));
                $row->setValue($this->cChar, $saveInfo->getPlayerName());
                $row->setValue($this->cMoney, $saveInfo->getMoneyPretty());
            }
            else
            {
                $date = $save->getDateModified();

                $row->setValue($this->cName, $save->getSaveName());
                $row->setValue($this->cChar, '-');
                $row->setValue($this->cMoney, '-');

                $row->setValue($this->cActions,
                    Button::create(t('Unpack'))
                        ->setIcon(Icon::unpack())
                        ->colorPrimary()
                        ->sizeExtraSmall()
                        ->link($save->getURLUnpack())
                );
            }

            /*
            if(!$save->hasBackup())
            {
                echo Button::create(t('Backup'))
                    ->setIcon(Icon::backup())
                    ->sizeSmall()
                    ->link($save->getURLBackup());
            }
            */

            if($this->isColEnabled(self::COL_SAVE_TYPE)) {
                $row->setValue($this->cType, $save->getTypeLabel());
            }

            $row->setDate($this->cModified, $date, true, true);
            $row->setBool($this->cBackup, $save->hasBackup());
        }

        return $this->grid->render();
    }
}
