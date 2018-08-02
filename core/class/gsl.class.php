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

class gsl extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = jeedom::getTmpFolder('gsl') . '/dependance';
		$request = realpath(dirname(__FILE__) . '/../../resources/node_modules/request');
		if (is_dir($request)) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install.sh ' . jeedom::getTmpFolder('gsl') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function pull() {
		$cmd = 'nodejs ' . realpath(dirname(__FILE__) . '/../../resources/') . '/google-location-sharing.js ' . config::byKey('google_user', 'gsl') . ' ' . config::byKey('google_password', 'gsl');
		$json = json_decode(com_shell::execute(system::getCmdSudo() . $cmd . ' 2>&1'), true);
		foreach ($json['log'] as $log) {
			log::add('gsl', $log['type'], $log['value']);
		}
		log::add('gsl', 'info', json_encode($json['result']));
		$gChange = false;
		foreach ($json['result'] as $location) {
			$eqLogic = eqLogic::byLogicalId($location['id'], 'gsl');
			if (!is_object($eqLogic)) {
				$eqLogic = new eqLogic();
				$eqLogic->setName($location['name']);
				$eqLogic->setLogicalId($location['id']);
				$eqLogic->setEqType_name('gsl');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->save();
			}
			$changed = $eqLogic->checkAndUpdateCmd('name', $location['name']) || $changed;
			$value = $location['lat'] . ',' . $location['long'];
			$changed = $eqLogic->checkAndUpdateCmd('coordinated', $value) || $changed;
			$cmdgeoloc = $eqLogic->getConfiguration('cmdgeoloc', null);
			if ($cmdgeoloc !== null) {
				$cmdUpdate = cmd::byId(str_replace('#', '', $cmdgeoloc));
				$cmdUpdate->event($value);
				$cmdUpdate->getEqLogic()->refreshWidget();
			}
			$changed = $eqLogic->checkAndUpdateCmd('image', $location['photoURL']) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('timestamp', $location['timestamp']) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('address', $location['address']) || $changed;
			if ($changed) {
				$gChange = true;
				$eqLogic->refreshWidget();
			}
		}
		if ($gChange) {
			$eqLogic = eqLogic::byLogicalId('global', 'gsl');
			if (is_object($eqLogic)) {
				$eqLogic->refreshWidget();
			}
		}
	}

	public static function cron() {
		$dateRun = new DateTime();
		$c = new Cron\CronExpression(config::byKey('refresh::frequency', 'gsl', '* * * * *'), new Cron\FieldFactory);
		if ($c->isDue($dateRun)) {
			self::pull();
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	public function preSave() {
		if ($this->getDisplay('height') == 'auto') {
			$this->setDisplay('height', '270px');
		}
		if ($this->getDisplay('width') == 'auto') {
			$this->setDisplay('width', '370px');
		}
	}

	public function postSave() {
		if ($this->getLogicalId() == 'global') {
			return;
		}
		$cmd = $eqLogic->getCmd(null, 'name');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('nom');
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('name');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $eqLogic->getCmd(null, 'coordinated');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('coordonnees');
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('coordinated');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $eqLogic->getCmd(null, 'image');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('image');
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('image');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $eqLogic->getCmd(null, 'timestamp');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('timestamp');
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('timestamp');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $eqLogic->getCmd(null, 'address');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('adresse');
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('address');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}
	}

	public function toHtml($_version = 'dashboard') {
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
				$html .= ('<hr/>');
			}
			$replace['#adresses#'] = $html;
			if ($version == 'dashboard') {
				$replace['#height-map#'] = $replace['#height#'] - 60;
			} else {

				$replace['#height-map#'] = $replace['#height#'] / 2;
			}
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl_global', 'gsl')));
		} else {
			$replace['#height-map#'] = $replace['#height#'] - 100;
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl', 'gsl')));
		}
	}

	public static function createGlobalEqLogic() {
		$eqLogic = eqLogic::byLogicalId('global', 'gsl');
		if (!is_object($eqLogic)) {
			$eqLogic = new eqLogic();
			$eqLogic->setName('Global');
			$eqLogic->setLogicalId('global');
			$eqLogic->setEqType_name('gsl');
			$eqLogic->setIsVisible(1);
			$eqLogic->setIsEnable(1);
			$eqLogic->save();
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}

class gslCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {

	}

	/*     * **********************Getteur Setteur*************************** */
}
