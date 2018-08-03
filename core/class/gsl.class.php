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
				if($location['id'] !== 'global'){
					$eqLogic->setConfiguration('isVisibleGlobal',1);
				}
				$eqLogic->setConfiguration('isVisiblePanel',1);
				$eqLogic->save();
			}
			$changed = false;
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
		$cmd = $this->getCmd(null, 'name');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('nom');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('name');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'coordinated');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('coordonnees');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('coordinated');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'image');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('image');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('image');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'timestamp');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('timestamp');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('timestamp');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'address');
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('adresse');
			$cmd->setEqLogic_id($this->getId());
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
		$replace['#logicalId#'] = $this->getLogicalId();
		if ($this->getLogicalId() == 'global') {
			$replace['#adresses#'] = '';
			$data = array();
			$eqLogics = self::byType('gsl', true);
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getLogicalId() == 'global') {
					continue;
				}
				if(!$eqLogic->getConfiguration('isVisibleGlobal',0)) {
					continue;
				}
				$data[$eqLogic->getId()] = $eqLogic->buildLocation();
				$replace['#adresses#'] .= '<img class="pull-right" style="margin-top:5px;with:50px; height:50px;border-radius: 50% !important;" src="' . $data[$eqLogic->getId()]['image'] . '" />';
				$replace['#adresses#'] .= '<span style="font-size:0.8em;">' . $data[$eqLogic->getId()]['name'] . '</span><br/>';
				$replace['#adresses#'] .= '<span>' . $data[$eqLogic->getId()]['address'] . '</span><br/>';
				$replace['#adresses#'] .= '<span style="font-size:0.7em;">' . $data[$eqLogic->getId()]['horodatage'] . '</span>';
				$replace['#adresses#'] .= '<hr/>';
			}
			$replace['#json#'] = str_replace("'", "\'", json_encode($data));
			$replace['#height-map#'] = ($version == 'dashboard') ? $replace['#height#'] - 60 : 170;
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl_global', 'gsl')));
		} else {
			$data = array($this->getId() => $this->buildLocation());
			$replace['#adresses#'] .= '<span>' . $data[$this->getId()]['address'] . '</span><br/>';
			$replace['#adresses#'] .= '<span style="font-size:0.7em;">' . $data[$this->getId()]['horodatage'] . '</span>';
			$replace['#json#'] = str_replace("'", "\'", json_encode($data));
			$replace['#height-map#'] = ($version == 'dashboard') ? $replace['#height#'] - 100 : 170;
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl', 'gsl')));
		}
	}


	public function buildLocation() {
		if ($this->getLogicalId() == 'global') {
			return;
		}
		$return = array(
			'id' => $this->getLogicalId(),
		);
		$cmds = $this->getCmd();
		foreach ($cmds as $cmd) {
			$return[$cmd->getLogicalId()] = $cmd->execCmd();
			if ($cmd->getName() != 'timestamp') {
				continue;
			}
			$timestamp = $return[$cmd->getLogicalId()];
			if (!$timestamp) {
				continue;
			}
			$return['horodatage'] = "le " . date("d/m/Y à H:i",$timestamp / 1000);
		}
		return $return;
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
