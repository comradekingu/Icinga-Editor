<?php

namespace Icinga\Editor;

/**
 * Icinga Editor - titulní strana
 *
 * @package    IcingaEditor
 * @subpackage WebUI
 * @author     Vitex <vitex@hippy.cz>
 * @copyright  2012 Vitex@hippy.cz (G)
 */
require_once 'includes/IEInit.php';

$oPage->onlyForLogged();

$serviceId      = $oPage->getRequestValue('service_id', 'int');
$owner_id       = $oPage->getRequestValue('user_id', 'int');
$name           = trim($oPage->getRequestValue('name'));
$use            = trim($oPage->getRequestValue('use'));
$remoteCmd      = trim($oPage->getRequestValue('check_command-remote'));
$remoteCmdParam = trim($oPage->getRequestValue('check_command-params'));
$platform       = trim($oPage->getRequestValue('platform'));
$service        = new Engine\Service($serviceId);
$service->owner = &$oUser;

if (isset($platform)) {
    $service->setDataValue('platform', $platform);
}


if ($name) {

    if (!isset($use)) {
        $use = 'generic-service';
    }

    if (($owner_id == 0) || ($owner_id == 'null')) {
        $owner_id = null;
    }

    $data = [
        $service->userColumn => $owner_id,
        'use' => $use,
        'generate' => true,
        'active_checks_enabled' => 1,
        'passive_checks_enabled' => 0,
        'check_freshness' => 1,
//          'freshness_threshold' => ?
        'check_command' => 'return-unknown'
    ];

    if ($oPage->getRequestValue('register', 'int')) {
        $data['service_description'] = $name;
        $data['display_name']        = $name;
        $data['register']            = true;
    } else {
        $data['name']     = $name;
        $data['register'] = false;
    }

    if ($oUser->getSettingValue('admin')) {
        $data['public'] = true;
    } else {
        $data['public'] = false;
    }

    if (isset($remoteCmd)) {
        $data['check_command-remote'] = $remoteCmd;
    }

    if (isset($remoteCmdParam)) {
        $data['check_command-params'] = $remoteCmdParam;
    }

    $service->setData($data);

    if ($service->saveToSQL()) {
        /*
          $serviceGroup = new Engine\ServiceGroup;
          if ($serviceGroup->loadDefault()) {
          $serviceGroup->setDataValue($serviceGroup->nameColumn, \Ease\Shared::user()->getUserLogin());
          $serviceGroup->addMember('members', $service->getId(), $service->getName());
          $serviceGroup->saveToSQL();
          }
         */
        if (strlen(trim($service->getDataValue('check_command-remote'))) && $data['register']) {
            $oPage->addStatusMessage(_('Služba byla založena'), 'success');
            $oPage->redirect('service.php?service_id='.$service->getId());
            exit();
        } else {
            $oPage->addStatusMessage(_('Není zvolen vzdálený příkaz testu'),
                'warning');
        }
    }
} else {
    if ($oPage->isPosted()) {
        $oPage->addStatusMessage(_('Prosím zastejte název služby'), 'warning');
    } else {
        $service->setDataValue($service->userColumn, $oUser->getUserID());
    }
}


$oPage->addItem(new UI\PageTop(_('Průvodce založením pasivně sledované služby')));
$oPage->addPageColumns();

$oPage->columnI->addItem(
    new \Ease\TWB\Panel(_('Aktivní checky'), 'info',
    _('senzor (nrpe/nscp.exe) běží na vzdáleném hostu, a výsledky nadefinovaných testů zasílá protokolem NRPE na monitorovací server.'))
);
$oPage->columnIII->addItem(
    new \Ease\TWB\Panel(_('Aktivně sledovaná služba'), 'info',
    _('Nabízené příkazy jsou definovány jako vzdálené a odpovídající zvolené platformě. Parametry záleží na konkrétně zvoleném příkazu testu.'))
);


$oPage->columnII->addItem(new UI\ServiceWizardForm($service));

$oPage->addItem(new UI\PageBottom());

$oPage->draw();
