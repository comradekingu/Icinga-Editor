<?php

namespace Icinga\Editor\UI;

/**
 * Volba platformy
 *
 * @package    IcingaEditor
 * @subpackage WebUI
 * @author     Vitex <vitex@hippy.cz>
 * @copyright  2012 Vitex@hippy.cz (G)
 */
class PlatformSelector extends \Ease\Html\Select
{
    public $platforms = [
        'generic' => ['image' => 'logos/unknown.gif'],
        'windows' => ['image' => 'logos/base/win40.gif'],
        'linux' => ['image' => 'logos/base/linux40.gif'],
    ];

    function loadItems()
    {
        return ['generic' => 'generic', 'windows' => 'windows', 'linux' => 'linux'];
    }

    public function finalize()
    {
        parent::finalize();
        reset($this->platforms);
        foreach ($this->pageParts as $optionName => $option) {
            $platform = current($this->platforms);
            if (isset($platform['image'])) {
                $this->pageParts[$optionName]->setTagProperties(['data-image' => $platform['image']]);
            }
            next($this->platforms);
        }
        \Ease\Shared::webPage()->addJavaScript('$("#'.$this->getTagID().'").msDropDown();',
            null, true);
        \Ease\Shared::webPage()->addJavaScript('$("#'.$this->getTagID().'").change(function() {
            var oDropdown = $("#'.$this->getTagID().'").msDropdown().data("dd");
            var text = oDropdown.get("selectedText");
            console.log(text);

        var saverClass = $("[name=\'class\']").val();
        var keyId = $(".keyId").val();
        var columnName = $(this).attr("name");

var jqxhr = $.post( "datasaver.php?SaverClass=" + saverClass , { Field: columnName, Value: text, Key: keyId }  ,   function() {
    console.log( "success" );
})
.done(function() {
    console.log( "second success" );
})
.fail(function() {
    console.log( "error" );
});
        });', null, true);
        \Ease\Shared::webPage()->includeJavaScript('js/msdropdown/jquery.dd.min.js');
        \Ease\Shared::webPage()->includeCss('css/msdropdown/dd.css');
    }
}