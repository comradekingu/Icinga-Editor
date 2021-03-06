<?php

namespace Icinga\Editor;

/**
 * Description of NRPEConfigGenerator
 *
 * @author vitex
 */
class NRPEConfigGenerator extends \Ease\Atom
{
    /**
     * Host object
     * @var Engine\Host
     */
    public $host = null;

    /**
     * Preferences
     * @var  Array
     */
    public $prefs = null;

    /**
     * Configuration fragments array
     * @var array
     */
    public $nscCfgArray = [];

    /**
     * How to call variable with nsclient
     * @var string
     */
    private $nscvar = '';

    /**
     * Config file destination on remote system
     * @var string
     */
    private $cfgFile = '/etc/nagios/nrpe_local.cfg';

    /**
     * Generátor konfigurace NSC++
     *
     * @param Engine\Host $host
     */
    public function __construct($host)
    {
        $this->host = $host;

        $preferences = new Preferences();
        $this->prefs = $preferences->getPrefs();
        $this->cfgInit();
        $this->cfgGeneralSet();

        $this->cfgServices();
        $this->cfgEnding();
    }

    /**
     * Připraví nouvou konfiguraci
     */
    function cfgInit()
    {
        $this->nscCfgArray = [
            '#!/bin/sh',
            'sudo -s -- <<EOF',
            'service nagios-nrpe-server stop',
            'mv '.$this->cfgFile.' '.$this->cfgFile.'.old'];
        $this->addCfg('dont_blame_nrpe', 1);
    }

    /**
     * Nakonfiguruje režim pasivních testů odesílaných přez NSCA
     */
    function cfgGeneralSet()
    {
        $this->addCfg('allowed_hosts', $this->prefs['serverip']);
    }

    /**
     * Konfigurace sluzeb
     */
    function cfgServices()
    {
        $service = new Engine\Service();

        $servicesAssigned = $service->dblink->queryToArray('SELECT '.$service->myKeyColumn.','.$service->nameColumn.',`use` FROM '.$service->myTable.' WHERE host_name LIKE \'%"'.$this->host->getName().'"%\'',
            $service->myKeyColumn);

        $allServices = $service->getListing(
            null, true,
            [
            'platform', 'check_command-remote', 'check_command-params', 'passive_checks_enabled',
            'active_checks_enabled', 'use', 'check_interval', 'check_command-remote'
            ]
        );

        foreach ($allServices as $serviceID => $serviceInfo) {
            if (!array_key_exists($serviceID, $servicesAssigned)) {
                unset($allServices[$serviceID]);
                continue; //Service is not assigned to host
            }
        }


        /* use presets values */
        $usedCache     = [];
        $commandsCache = [];
        foreach ($allServices as $rowId => $service) {
            if (isset($service['use'])) {
                $remote = $service['check_command-remote'];
                if (!isset($commandsCache[$remote])) {
                    $command                = new Engine\Command($remote);
                    $commandsCache[$remote] = $command->getData();
                }
            }

            if (isset($service['use'])) {
                $use = $service['use'];

                if (!isset($usedCache[$use])) {
                    $used             = new Engine\Service;
                    $used->nameColumn = 'name';

                    if ($used->loadFromSQL($use)) {
                        $used->resetObjectIdentity();
                        $usedCache[$use] = $used->getData();
                    }
                }
                if (isset($usedCache[$use])) {
                    foreach ($usedCache[$use] as $templateKey => $templateValue) {
                        if ($templateKey != 'check_interval') {
                            continue;
                        }
                        if (!is_null($templateValue) && !isset($allServices[$rowId][$templateKey])) {
                            $allServices[$rowId][$templateKey] = $templateValue;
                        }
                    }
                }
            }
        }

        foreach ($allServices as $serviceId => $service) {

            $serviceName = $service['service_description'];
            $serviceCmd  = $service['check_command-remote'];
            if (is_null($serviceCmd)) {
                continue;
            }
            $serviceParams       = $service['check_command-params'];
            $this->nscCfgArray[] = "\n# #".$service['service_id'].' '.$serviceName;

            if (isset($commandsCache[$serviceCmd])) {
                if (isset($commandsCache[$serviceCmd]['deploy'])) {
                    $this->nscCfgArray[] = $commandsCache[$serviceCmd]['deploy'];
                }
                $cmdline = $commandsCache[$serviceCmd]['command_line'];
            } else {
                $cmdline = $serviceCmd;
            }

            $this->addCfg('command['.str_replace(' ', '_', $serviceName).']',
                $cmdline.' '.$serviceParams);
        }
    }

    /**
     * Test service and run it
     */
    public function cfgEnding()
    {
        $this->nscCfgArray[] = "\n".'curl "'.$this->getCfgConfirmUrl().'"';
        $this->nscCfgArray[] = 'service nagios-nrpe-server start';
        $this->nscCfgArray[] = 'EOF';
        $this->nscCfgArray[] = '';
    }

    /**
     * vrací vyrendrovaný konfigurační skript
     */
    public function getCfg($send = TRUE)
    {
        $nrpesh = implode("\n", $this->nscCfgArray);
        if ($send) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Disposition: attachment; filename='.$this->host->getName().'_nrpe.sh');
            header('Content-Length: '.strlen($nrpesh));
            echo $nrpesh;
        } else {
            return $nrpesh;
        }
    }

    /**
     * Vrací URL konfiguračního rozhraní
     *
     * @return string
     */
    function getBaseURL()
    {
        if (isset($_SERVER['REQUEST_SCHEME'])) {
            $scheme = $_SERVER['REQUEST_SCHEME'];
        } else {
            $scheme = 'http';
        }

        $enterPoint = $scheme.'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']).'/';

//        $enterPoint = str_replace('\\', '', $enterPoint); //Win Hack
        return $enterPoint;
    }

    function getCfgConfirmUrl()
    {
        return $this->getBaseURL().'cfgconfirm.php?hash='.$this->host->getConfigHash().'&host_id='.$this->host->getId();
    }

    public function addCfg($key, $value)
    {
        $this->nscCfgArray[] = "echo \"$key=$value\" >> ".$this->cfgFile;
    }

}
