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
$serviceName    = trim($oPage->getRequestValue('service_name'));
$remoteCmd      = trim($oPage->getRequestValue('check_command-remote'));
$remoteCmdParam = trim($oPage->getRequestValue('check_command-params'));
$platform       = trim($oPage->getRequestValue('platform'));
$service        = new Engine\IEService($serviceId);
$service->owner = &$oUser;

if (isset($platform)) {
    $service->setDataValue('platform', $platform);
}


if ($serviceName) {

    $data = [
        $service->userColumn => $oUser->getUserID(),
        'service_description' => $serviceName,
        'use' => 'generic-service',
        'register' => true,
        'generate' => true,
        'display_name' => $serviceName,
        'active_checks_enabled' => 0,
        'passive_checks_enabled' => 1,
        'check_freshness' => 1,
//          'freshness_threshold' => ?
        'check_command' => 'return-unknown'
    ];

    if (isset($remoteCmd)) {
        $data['check_command-remote'] = $remoteCmd;
    }

    if (isset($remoteCmdParam)) {
        $data['check_command-params'] = $remoteCmdParam;
    }

    $service->setData($data);

    if ($service->saveToSQL()) {
        /*
          $serviceGroup = new Engine\IEServiceGroup;
          if ($serviceGroup->loadDefault()) {
          $serviceGroup->setDataValue($serviceGroup->nameColumn, \Ease\Shared::user()->getUserLogin());
          $serviceGroup->addMember('members', $service->getId(), $service->getName());
          $serviceGroup->saveToSQL();
          }
         */
        if (strlen(trim($service->getDataValue('check_command-remote')))) {
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
    }
}


$oPage->addItem(new UI\PageTop(_('Průvodce založením pasivně sledované služby')));
$oPage->addPageColumns();

$oPage->columnI->addItem(
    new \Ease\TWB\Panel(_('Pasivní checky'), 'info',
    _('senzor (nrpe/nscp.exe) běží na vzdáleném hostu, který je z monitorovacího serveru nedostupný (např. za NATem) ale má přístup do internetu a tak výsledky nadefinovaných testů zasílá protokolem NSCA na monitorovací server, který je přímá a zpracovává jako by se jednalo o výsledky aktivních testů.'))
);
$oPage->columnIII->addItem(
    new \Ease\TWB\Panel(_('Pasivně sledovaná služba'), 'info',
    _('Nabízené příkazy jsou definovány jako vzdálené a odpovídající zvolené platformě. Parametry záleží na konkrétně zvoleném příkazu testu.'))
);

$oPage->columnII->addItem(new UI\ServiceWizardForm($service));

$oPage->addItem(new UI\PageBottom());

$oPage->draw();
