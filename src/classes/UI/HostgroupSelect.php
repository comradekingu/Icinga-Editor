<?php

namespace Icinga\Editor\UI;

/**
 * Description of IEServiceSelect
 *
 * @author vitex
 */
class HostgroupSelect extends \Ease\Html\Select
{

    function loadItems()
    {
        $membersFound = array('' => '---');
        $query        = 'SELECT  `hostgroup_id`, `hostgroup_name` FROM `'.'hostgroup` WHERE (user_id='.$this->user->getUserID().')  ORDER BY  hostgroup_name ';

        $membersFoundArray = \Ease\Shared::myDbLink()->queryToArray($query);
        if (count($membersFoundArray)) {
            foreach ($membersFoundArray as $request) {
                $membersFound[$request['hostgroup_id']] = $request['hostgroup_name'];
            }
        }
        return $membersFound;
    }

    public function finalize()
    {
        parent::finalize();
        \Ease\Shared::webPage()->addJavaScript('$("#'.$this->getTagID().'").msDropDown();',
            null, true);
        \Ease\Shared::webPage()->includeJavaScript('js/msdropdown/jquery.dd.min.js');
        \Ease\Shared::webPage()->includeCss('css/msdropdown/dd.css');
    }
}