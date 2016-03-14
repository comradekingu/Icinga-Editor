<?php
namespace Icinga\Editor;

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
require_once 'classes/IEDataGrid.php';

$oPage->onlyForLogged();

$oPage->addItem(new UI\PageTop(_('Přehled příkazů')));

$oPage->addItem(new \Ease\TWB\Container(new IEDataGrid(_('Příkazy'), new IECommand)));

$oPage->addItem(new UI\PageBottom());

$oPage->draw();