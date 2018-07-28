<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class gsl extends eqLogic
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    public static function pull()
    {
        $service_path = realpath(dirname(__FILE__) . '/../../resources/');
        $user = config::byKey('google_user', 'gsl');
        $password = config::byKey('google_password', 'gsl');
        $cmd = 'nodejs ' . $service_path . '/google-location-sharing.js ' . $user . ' ' . $password;// . ' ' . $i . ' ' . $interface;
        $result = exec('sudo ' . $cmd . ' 2>&1');
        $json = json_decode($result, true);
        foreach ($json["log"] as $log) {
            log::add('gsl', $log["type"], $log["value"]);
        }
        log::add('gsl', 'info', json_encode($json["result"]));
        foreach ($json["result"] as $location) {
            $eqLogic = eqLogic::byLogicalId($location["id"], 'gsl');
            if (!is_object($eqLogic)) {
                $eqLogic = new eqLogic();
                $eqLogic->setName($location["name"]);
                $eqLogic->setLogicalId($location["id"]);
                $eqLogic->setEqType_name('gsl');
                $eqLogic->setDisplay('showOnDashboard', 1);
                $eqLogic->setIsVisible(1);
                $eqLogic->setIsEnable(1);
                $eqLogic->save();
                $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'nom');
                if (!is_object($cmd)) {
                    $cmd = new cmd();
                    $cmd->setName('nom');
                    $cmd->setEqLogic_id($eqLogic->getId());
                    $cmd->setLogicalId('nom');
                    $cmd->setType('info');
                    $cmd->setSubType('string');
                    $cmd->save();
                }
                $cmd->event($location["name"]);
            }
            $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'coordonnees');
            if (!is_object($cmd)) {
                $cmd = new cmd();
                $cmd->setName('coordonnees');
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setLogicalId('coordonnees');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->save();
            }
            $value = $location["lat"] . ',' . $location["long"];

            $cmd->event($value);
            $cmdgeoloc = $eqLogic->getConfiguration("cmdgeoloc", null);
            if ($cmdgeoloc !== null) {
                $cmdUpdate = cmd::byString($cmdgeoloc);
                $cmdUpdate->event($value);
                $cmdUpdate->getEqLogic()->refreshWidget();
            }
            $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'image');
            if (!is_object($cmd)) {
                $cmd = new cmd();
                $cmd->setName('image');
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setLogicalId('image');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->save();
            }
            $cmd->event($location["photoURL"]);

            $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'timestamp');
            if (!is_object($cmd)) {
                $cmd = new cmd();
                $cmd->setName('timestamp');
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setLogicalId('timestamp');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->save();
            }
            $cmd->event($location["timestamp"]);
            $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'adresse');
            if (!is_object($cmd)) {
                $cmd = new cmd();
                $cmd->setName('adresse');
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setLogicalId('adresse');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->save();
            }
            $cmd->event($location["address"]);

            if ($eqLogic != null) {
                $eqLogic->refreshWidget();
            }
        }
        $eqLogic = eqLogic::byLogicalId('global', 'gsl');
        if (is_object($eqLogic)) {
            $eqLogic->refreshWidget();
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom*/
    public static function cron()
    {
        if (config::byKey('refreshCron', 'gsl', 'cron5') === 'cron') {
            self::pull();
        } else {
            $equipements = eqLogic::byType('gsl', true);
            foreach ($equipements as $eqLogic) {
                if (is_object($eqLogic)) {
                    $eqLogic->refreshWidget();
                }
            }
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom*/
    public static function cron5()
    {
        if (config::byKey('refreshCron', 'gsl', 'cron5') === 'cron5') {
            self::pull();
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom*/
    public static function cron15()
    {
        if (config::byKey('refreshCron', 'gsl', 'cron5') === 'cron15') {
            self::pull();
        }
    }

    /*
         * Fonction exécutée automatiquement toutes les minutes par Jeedom*/
    public static function cron30()
    {
        if (config::byKey('refreshCron', 'gsl', 'cron5') === 'cron30') {
            self::pull();
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom*/
    public static function cronHourly()
    {
        if (config::byKey('refreshCron', 'gsl', 'cron5') === 'cronHourly') {
            self::pull();
        }
    }


    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */


    /*     * *********************Méthodes d'instance************************* */

    public function preInsert()
    {

    }

    public function postInsert()
    {

    }

    public function preSave()
    {
        if ($this->getDisplay('height') == 'auto') {
            $this->setDisplay('height', '270px');
        }
        if ($this->getDisplay('width') == 'auto') {
            $this->setDisplay('width', '370px');
        }
    }

    public function postSave()
    {

    }

    public function preUpdate()
    {

    }

    public function postUpdate()
    {

    }

    public function preRemove()
    {

    }

    public function postRemove()
    {

    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin*/
    public function toHtml($_version = 'dashboard')
    {
        $replace = $this->preToHtml($_version, array(), true);
        if (!is_array($replace)) {
            return $replace;
        }
        $version = jeedom::versionAlias($_version);
        $replace['#text_color#'] = $this->getConfiguration('text_color');
        $replace['#version#'] = $_version;
        $logicalId = $this->getLogicalId();

        $replace['#logicalId#'] = $logicalId;
        $replace['#script#'] = '<script src="/plugins/gsl/desktop/js/gsl.js"></script><script>createMap(' . $this->getId() . ', "' . $logicalId . '"); </script>';

        if ($logicalId == 'global') {
            $html = '';
            $equipements = $this::byType('gsl', true);
            foreach ($equipements as $eq) {
                if ($eq->getLogicalId() == 'global') {
                    continue;
                }
                $html .= ('<img style="with:50px; height:50px;border-radius: 50%;" class="image_' . $eq->getLogicalId() . '"/>');
                $html .= ('<span class="nom_' . $eq->getLogicalId() . '"></span>');
                $html .= ('<div class="adresse_' . $eq->getLogicalId() . '"></div>');
                $html .= ('<div class="horodatage_' . $eq->getLogicalId() . '"></div>');
                $html .= ('<hr />');
            }
            $replace['#adresses#'] = $html;
            if($version == 'dashboard') {
                $replace['#height-map#'] = $replace['#height#'] - 60;
            }else{

                $replace['#height-map#'] = $replace['#height#'] / 2;
            }
            return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl_global', 'gsl')));
        } else {

            $replace['#height-map#'] = $replace['#height#'] - 100;
            return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl', 'gsl')));
        }
    }

    public static function createGlobalEqLogic()
    {
        $eqLogic = eqLogic::byLogicalId('global', 'gsl');
        if (!is_object($eqLogic)) {
            $eqLogic = new eqLogic();
            $eqLogic->setName('Global');
            $eqLogic->setLogicalId('global');
            $eqLogic->setEqType_name('gsl');
            $eqLogic->setDisplay('showOnDashboard', 1);
            $eqLogic->setIsVisible(1);
            $eqLogic->setIsEnable(1);
            $eqLogic->save();
        }
    }

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
    
   
     */

    public static function dependancy_info()
    {
        $return = array();
        $return['log'] = 'gsl_dep';
        $request = realpath(dirname(__FILE__) . '/../../resources/node_modules/request');
        $return['progress_file'] = '/tmp/gsl_dep';
        if (is_dir($request)) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        return $return;
    }

    public static function dependancy_install()
    {
        log::add('gsl', 'info', 'Installation des dépéndances nodejs');
        $resource_path = realpath(dirname(__FILE__) . '/../../resources');
        passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' gsl > ' . log::getPathToLog('gsl_dep') . ' 2>&1 &');
    }

    /*     * **********************Getteur Setteur*************************** */
}

class gslCmd extends cmd
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array())
    {

    }

    /*     * **********************Getteur Setteur*************************** */
}


