<?php

/**
 * Icinga Editor - přehled příkazů
 * 
 * @package    IcingaEditor
 * @subpackage WebUI
 * @author     Vitex <vitex@hippy.cz>
 * @copyright  2012 Vitex@hippy.cz (G)
 */
require_once 'includes/IEInit.php';
require_once 'classes/IECommand.php';

$oPage->onlyForLogged();

$oPage->addItem(new IEPageTop(_('Přehled příkazů')));




$Command = new IECommand();
$Commands = $Command->getListing(null,null,array('command_local','command_remote','public'));



if ($Commands) {
    $cntList = new EaseHtmlTableTag(null, array('class' => 'table'));

    $Cid = 1;
    foreach ($Commands as $CID => $CInfo) {
        if (intval($CInfo['command_local'])) {
            $lastRow = $cntList->addRowColumns(array($Cid++, new EaseHtmlATag('command.php?command_id=' . $CInfo['command_id'], $CInfo['command_name'])));
            if ($CInfo['generate'] == 0) {
                $lastRow->setTagCss(array('border-right' => '1px solid red'));
            }
            if ($CInfo['public'] == 1) {
                if ($CInfo[$Command->userColumn] == $oUser->getUserID()) {
                    $lastRow->setTagCss(array('border-left' => '1px solid green'));
                } else {
                    $lastRow->setTagCss(array('border-left' => '1px solid blue'));
                }
            }
            unset($Commands[$CID]);
        }
    }
    $oPage->columnI->addItem(new EaseHtmlH4Tag(_('Místní příkazy')));
    $oPage->columnI->addItem($cntList);

    $cntList = new EaseHtmlTableTag(null, array('class' => 'table'));
    $Cid = 1;
    foreach ($Commands as $CID => $CInfo) {
        if (intval($CInfo['command_remote'])) {
            $lastRow = $cntList->addRowColumns(array($Cid++, new EaseHtmlATag('command.php?command_id=' . $CInfo['command_id'], $CInfo['command_name'])));
            if ($CInfo['generate'] == 0) {
                $lastRow->setTagCss(array('border-right' => '1px solid red'));
            }
            if ($CInfo['public'] == 1) {
                if ($CInfo[$Command->userColumn] == $oUser->getUserID()) {
                    $lastRow->setTagCss(array('border-left' => '1px solid green'));
                } else {
                    $lastRow->setTagCss(array('border-left' => '1px solid blue'));
                }
            }
            unset($Commands[$CID]);
        }
        
    }
    $oPage->columnII->addItem(new EaseHtmlH4Tag(_('vzdálené příkazy')));
    $oPage->columnII->addItem($cntList);

    $cntList = new EaseHtmlTableTag(null, array('class' => 'table'));
    foreach ($Commands as $CID => $CInfo) {
            $lastRow = $cntList->addRowColumns(array($Cid++, new EaseHtmlATag('command.php?command_id=' . $CInfo['command_id'], $CInfo['command_name'])));
            if ($CInfo['generate'] == 0) {
                $lastRow->setTagCss(array('border-right' => '1px solid red'));
            }
            if ($CInfo['public'] == 1) {
                if ($CInfo[$Command->userColumn] == $oUser->getUserID()) {
                    $lastRow->setTagCss(array('border-left' => '1px solid green'));
                } else {
                    $lastRow->setTagCss(array('border-left' => '1px solid blue'));
                }
            }
            unset($Commands[$CID]);
    }
    $oPage->columnIII->addItem(new EaseHtmlH4Tag(_('neurčené příkazy')));
    $oPage->columnIII->addItem($cntList);
    
    
    
} else {
    $oUser->addStatusMessage(_('Nemáte definovaný příkaz'), 'warning');
}

$oPage->columnIII->addItem(new EaseTWBLinkButton('command.php', _('Založit příkaz') . ' '.EaseTWBPart::GlyphIcon('edit')));
$oPage->columnIII->addItem(new EaseTWBLinkButton('importcommand.php', _('Importovat příkazy') . ' <i class="icon-download"></i>'));


$oPage->addItem(new IEPageBottom());


$oPage->draw();
?>
