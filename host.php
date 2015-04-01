<?php

/**
 * Icinga Editor hosta
 *
 * @package    IcingaEditor
 * @subpackage WebUI
 * @author     Vitex <vitex@hippy.cz>
 * @copyright  2012 Vitex@hippy.cz (G)
 */
require_once 'includes/IEInit.php';

$oPage->onlyForLogged();

$hostId = $oPage->getRequestValue('host_id', 'int');

if ($hostId == 0) {
    $oPage->redirect('hosts.php');
    exit();
}

$host = new IEHost($hostId);

switch ($oPage->getRequestValue('action')) {
    case 'applystemplate':
        $stemplate = new IEStemplate($oPage->getRequestValue('stemplate_id', 'int'));
        $services = $stemplate->getDataValue('services');
        if (count($services)) {
            $service = new IEService;
            foreach ($services as $service_id => $service_name) {
                $service->loadFromMySQL($service_id);
                $service->addMember('host_name', $host->getId(), $host->getName());
                $service->saveToMySQL();
                $service->dataReset();
            }
        }
        $contacts = $stemplate->getDataValue('contacts');
        if (count($contacts)) {
            foreach ($contacts as $contact_id => $contact_name) {
                $host->addMember('contacts', $contact_id, $contact_name);
            }
            $host->saveToMySQL();
        }

        break;
    case 'populate':
        $host->autoPopulateServices();
        break;
    case 'icon':

        $icourl = $oPage->getRequestValue('icourl');
        if (strlen($icourl)) {
            $tmpfilename = sys_get_temp_dir() . '/' . EaseSand::randomString();
            $fp = fopen($tmpfilename, 'w');
            $ch = curl_init($icourl);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            $data = curl_exec($ch);

            $downloadErr = curl_error($ch);
            if ($downloadErr) {
                $oPage->addStatusMessage($downloadErr, 'warning');
            }
            curl_close($ch);
            fclose($fp);

            if (!file_exists($tmpfilename)) {
                $oPage->addStatusMessage(sprintf(_('Soubor %s se nepodařilo stahnout'), $icourlurl));
            }
        } else {
            if (isset($_FILES) && count($_FILES)) {
                $tmpfilename = $_FILES['icofile']['tmp_name'];
            } else {
                if ($oPage->isPosted()) {
                    $oPage->addStatusMessage(_('Nebyl vybrán soubor s ikonou hosta'), 'warning');
                }
            }
        }
        if (isset($tmpfilename)) {
            if (IEIconSelector::imageTypeOK($tmpfilename)) {
                $newicon = IEIconSelector::saveIcon($tmpfilename, $host);
            } else {
                unlink($tmpfilename);
                $oPage->addStatusMessage(_('toto není obrázek požadovaného typu'), 'warning');
            }
        }
    case 'newicon':
        if (!isset($newicon)) {
            $newicon = $oPage->getRequestValue('newicon');
        }
        if (strlen($newicon)) {
            $host->setDataValue('icon_image', $newicon);
            $host->setDataValue('statusmap_image', $newicon);
            $host->setDataValue('icon_image_alt', $oPage->getRequestValue('icon_image_alt'));
            if ($host->saveToMySQL()) {
                $oUser->addStatusMessage(_('Ikona byla přiřazena'), 'success');
            } else {
                $oUser->addStatusMessage(_('Ikona nebyla přiřazena'), 'warning');
            }
        }
        break;
    case 'rename':
        $newname = $oPage->getRequestValue('newname');
        if (strlen($newname)) {
            if ($host->rename($newname)) {
                $oUser->addStatusMessage(_('Host byl přejmenován'), 'success');
            } else {
                $oUser->addStatusMessage(_('Host nebyl přejmenován'), 'warning');
            }
        }
        break;
    case 'parent':
        $np = $oPage->getRequestValue('newparent');
        if ($np) {
            $newParent = EaseShared::myDbLink()->queryToValue('SELECT `' . $host->nameColumn . '` FROM ' . $host->myTable . ' '
                . 'WHERE `' . $host->nameColumn . '` = \'' . addSlashes($np) . '\' '
                . 'OR `alias` = \'' . addSlashes($np) . '\' '
                . 'OR `address` = \'' . addSlashes($np) . '\' '
                . 'OR `address6` = \'' . addSlashes($np) . '\' ');
            if (!$newParent) {
                $oUser->addStatusMessage(_('Rodič nebyl nalezen'), 'warning');
            } else {
                $currentParents = $host->getDataValue('parents');
                $currentParents[] = $newParent;
                $host->setDataValue('parents', $currentParents);
                $hostID = $host->saveToMySQL();
                if (is_null($hostID)) {
                    $oUser->addStatusMessage(_('Rodič nebyl přidán'), 'warning');
                } else {
                    $oUser->addStatusMessage(_('Rodič byl přidán'), 'success');
                }
            }
        }
        break;
    default:
        if ($oPage->isPosted()) {
            $host->takeData($_POST);
            $hostID = $host->saveToMySQL();
            if (is_null($hostID)) {
                $oUser->addStatusMessage(_('Host nebyl uložen'), 'warning');
            } else {
                $oUser->addStatusMessage(_('Host byl uložen'), 'success');
            }
        } else {
            $use = $oPage->getGetValue('use');
            if ($use) {
                if ($host->loadTemplate($use)) {
                    $host->setDataValue('use', $use);
                    $host->setDataValue('register', 1);
                }
            }

            $delete = $oPage->getGetValue('delete', 'bool');
            if ($delete == 'true') {
                $host->delete();
                $oPage->redirect('hosts.php');
                exit();
            }

            IEUsedServiceSelector::saveMembers($_REQUEST);
            $host->saveMembers();
        }
        break;
}

$delcnt = $oPage->getGetValue('delcontact');
if ($delcnt) {
    $host->delMember(
        'contacts', $oPage->getGetValue('contact_id', 'int'), $delcnt
    );
    $host->saveToMySql();
}

$addcnt = $oPage->getGetValue('addcontact');
if ($addcnt) {
    $host->addMember(
        'contacts', $oPage->getGetValue('contact_id', 'int'), $addcnt
    );
    $host->saveToMySql();
}

$oPage->addItem(new IEPageTop(_('Editace hosta') . ' ' . $host->getName()));

$infopanel = new IEInfoBox($host);
$tools = new EaseTWBPanel(_('Nástroje'), 'warning');

$pageRow = new EaseTWBRow;
$pageRow->addColumn(2, $infopanel);
$mainPanel = $pageRow->addColumn(6);
$pageRow->addColumn(4, $tools);
$oPage->container->addItem($pageRow);


$hostPanel = $mainPanel->addItem(new EaseTWBPanel(new EaseHtmlH1Tag($host->getDataValue('alias') . ' <small>' . $host->getName() . '</small>')));

$hostTabs = $hostPanel->addItem(new EaseTWBTabs('hostTabs'));
$commonTab = $hostTabs->addTab(_('Obecné'));
$hostParams = $hostTabs->addTab(_('Konfigurace'));

switch ($oPage->getRequestValue('action')) {
    case 'parent':
        require_once 'classes/IEParentSelector.php';
        $commonTab->addItem(new IEParentSelector($host));
        break;
    case 'icon':
        $commonTab->addItem(new IEIconSelector($host));
        break;
    case 'delete':
        $confirmator = $oPage->columnII->addItem(new EaseTWBPanel(_('Opravdu smazat ?')), 'danger');
        $confirmator->addItem(new EaseTWBLinkButton('?' . $host->myKeyColumn . '=' . $host->getID(), _('Ne') . ' ' . EaseTWBPart::glyphIcon('ok'), 'success'));
        $confirmator->addItem(new EaseTWBLinkButton('?delete=true&' . $host->myKeyColumn . '=' . $host->getID(), _('Ano') . ' ' . EaseTWBPart::glyphIcon('remove'), 'danger'));

        $infopanel->addItem($host->ownerLinkButton());

        break;
    default :

        $hostEdit = new IECfgEditor($host);
        $form = $hostParams->addItem(new EaseHtmlForm('Host', 'host.php', 'POST', $hostEdit, array('class' => 'form-horizontal')));
        $form->setTagID($form->getTagName());
        $form->addItem('<br>');
        $form->addItem(new EaseTWSubmitButton(_('Uložit'), 'success'));
        $oPage->addCss('
input.ui-button { width: 100%; }
');

        $tools->addItem($host->deleteButton());

        if ($host->getDataValue('active_checks_enabled') == '1') {
            $tools->addItem(new EaseTWBLinkButton('?action=populate&host_id=' . $host->getID(), _('Oskenovat a sledovat služby'), null, array('onClick' => "$('#preload').css('visibility', 'visible');")));
            $oPage->addItem(new EaseHtmlDivTag('preload', new IEFXPreloader(), array('class' => 'fuelux')));
        }

        $renameForm = new EaseTWBForm('Rename', '?action=rename&amp;host_id=' . $host->getID());
        $renameForm->addItem(new EaseHtmlInputTextTag('newname'), $host->getName(), array('class' => 'form-control'));
        $renameForm->addItem(new EaseTWSubmitButton(_('Přejmenovat'), 'success'));

        $tools->addItem(new EaseTWBPanel(_('Přejmenování'), 'info', $renameForm));
        $tools->addItem(new EaseTWBLinkButton('?action=parent&host_id=' . $host->getId(), _('Přiřadit rodiče'), 'success'));
        $tools->addItem(new EaseTWBLinkButton('?action=icon&host_id=' . $host->getId(), _('Změnit ikonu'), 'success'));

        if ($host->getDataValue('platform') != 'generic') {
            $tools->addItem(new EaseTWBLinkButton('sensor.php?host_id=' . $host->getId(), _('Nasadit senzor'), 'info'));
        }

        $commonTab->addItem(new IEUsedServiceSelector($host));
        $commonTab->addItem(new IEContactSelector($host));

        break;
}

$oPage->addItem(new IEPageBottom());

$oPage->draw();
