<?php
/**
 * Disk drive select Form
 *
 * @author     Vitex <vitex@hippy.cz>
 * @copyright  2014-2017 Vitex@hippy.cz (G)
 */

namespace Icinga\Editor\modules;

/**
 * Description of ping
 *
 * @author vitex
 */
class DriveLetter extends \Icinga\Editor\UI\ServiceConfigurator
{

    /**
     *
     */
    public function form()
    {
        $config = [
            'Drive' => null
        ];
        foreach (explode(' ', $this->commandParams[0]) as $cfg) {
            if (strstr($cfg, '=')) {
                list($key, $value) = explode('=', $cfg);
                $config[$key] = $value;
            } else {
                $config[$cfg] = null;
            }
        }

        $drives = array_combine(range('a', 'z'), range('A', 'Z'));

        unset($drives[1]);
        foreach ($drives as $did => $dname) {
            $drives[$did] = $drives[$did].':';
        }



        if (!strlen($config['Drive'])) {
            $this->form->addInput(new \Ease\Html\Select('Drive', $drives, '\\\\'),
                _('Disk'), 'X:',
                _('Select the drive letter followed by the drive letter'));
        } else {
            $this->form->addInput(new \Ease\Html\Select('Drive', $drives,
                str_replace(':', '', $config['Drive'])), _('Drive'), 'X:',
                _('Select the drive letter followed by the drive letter'));
        }
    }

    /**
     * Form processing
     *
     * @return boolean
     */
    public function reconfigureService()
    {
        $config = [];
        $page   = \Ease\Shared::webPage();

        foreach ($page->getRequestValues() as $key => $value) {
            switch ($key) {
                case 'Drive':

                    if (strlen(trim($value)) && ($value != '')) {
                        $config['Drive'] = $value.':';
                        $nameColumn      = $this->tweaker->service->nameColumn;
                        $newName         = $this->tweaker->service->getDataValue($nameColumn).' '._('Disc Drive').' '.strtoupper($value).':';

                        $this->tweaker->service->setDataValue($nameColumn,
                            $newName);
                        $this->tweaker->service->setDataValue('display_name',
                            sprintf(_('Disc Drive %s: '), strtoupper($value)));
                    }

                    break;

                default :
                    break;
            }
        }



        if (count($config)) {

            $this->tweaker->service->setDataValue('check_command-params',
                implode(' ', $config));

            return parent::reconfigureService();
        }

        return FALSE;
    }

}
