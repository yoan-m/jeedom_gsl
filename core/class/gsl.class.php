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
	public static $_widgetPossibility = array('custom' => true);
  	public static $_cookiePath = __DIR__ . '/../config/cookies.txt';
	/*     * ***********************Methode static*************************** */

	public static function distance($_a, $_b) {
		$a = explode(',', $_a);
		$b = explode(',', $_b);
		$earth_radius = 6378.137;
		$rlo1 = deg2rad($a[0]);
		$rla1 = deg2rad($a[1]);
		$rlo2 = deg2rad($b[0]);
		$rla2 = deg2rad($b[1]);
		$dlo = ($rlo2 - $rlo1) / 2;
		$dla = ($rla2 - $rla1) / 2;
		$a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
		$d = 2 * atan2(sqrt($a), sqrt(1 - $a));
		return round(($earth_radius * $d), 2);
	}

	public static function google_callLocationUrl() {
		log::add('gsl', 'debug', __('google_callLocationUrl ', __FILE__));
		$ch = curl_init('https://www.google.com/maps/preview/locationsharing/read?authuser=0&pb=');
		curl_setopt($ch, CURLOPT_COOKIEJAR, self::$_cookiePath);
		curl_setopt($ch, CURLOPT_COOKIEFILE,self::$_cookiePath);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		$headers = self::get_headers_from_curl_response($response);
		log::add('gsl', 'debug', __('Location data : Connection réussie, reponse : ', __FILE__) . $info['http_code']);
		if (empty($info['http_code']) || $info['http_code'] != 200) {
			throw new Exception(__('Erreur données de localisation code retour invalide : ', __FILE__) . $info['http_code'] . ' => ' . json_encode($headers));
		}
		$result = substr($response, $info['header_size'] + 4);
		if (!is_json($result)) {
			throw new Exception(__('Erreur données de localisation n\'est pas un json valide : ', __FILE__) . $result);
		}
		$result = json_decode($result, true);
      	log::add('gsl', 'debug', __('Location data : Connection réussie, reponse : ', __FILE__) .json_encode($result));
		
		if (!isset($result[0])) {
			throw new Exception(__('Erreur données de localisation invalide ou vide : ', __FILE__) . json_encode($result));
		}
		return $result;
	}

	public static function google_locationData() {
		if (!file_exists(self::$_cookiePath)) {
			log::add('gsl', 'error', __('Cookie absent, veuillez consulter la documentation pour configurer le plugin.', __FILE__));
		}
		try {
			$result = self::google_callLocationUrl();
		} catch (Exception $e) {
			//self::google_connect();
			$result = self::google_callLocationUrl();
		}
		$result = $result[0];
		$return = array();
		foreach ($result as $user) {
			$return[] = array(
				'id' => $user[0][0],
				'name' => $user[0][3],
				'image' => $user[0][1],
				'address' => $user[1][4],
				'timestamp' => $user[1][2],
				'coordinated' => $user[1][1][2] . ',' . $user[1][1][1],
				'battery' => $user[13][1],
				'charging' => $user[13][0],
              	'accuracy' => $user[1][3]
			);
		}
		return $return;
	}

	public static function google_logout() {
		if (!file_exists(self::$_cookiePath)) {
			return;
		}
		unlink(self::$_cookiePath);
	}


	public static function processCookies($_cookie) {
		return array(explode('=', explode(';', $_cookie)[0])[0] => explode('=', explode(';', $_cookie)[0])[1]);
	}

	public static function get_headers_from_curl_response($response) {
		$headers = array();
		$header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
		foreach (explode("\r\n", $header_text) as $i => $line) {
			if ($i === 0) {
				$headers['http_code'] = $line;
			} else {
				list($key, $value) = explode(': ', $line);

				$headers[$key] = $value;
			}
		}
		return $headers;
	}
  
  public static function google_saveCookie($_cookie){
		$fp = fopen(self::$_cookiePath, 'w');
		fwrite($fp, $_cookie);
		fclose($fp);
  }

	public static function pull($_force = false) {
		if (!$_force) {
			$dateRun = new DateTime();
			$c = new Cron\CronExpression(config::byKey('refresh::frequency', 'gsl', '*/10 * * * *'), new Cron\FieldFactory);
			if (!$c->isDue($dateRun)) {
				return;
			}
			sleep(rand(0, 90));
		}
		$gChange = false;
		foreach (self::google_locationData() as $location) {
			$eqLogic = eqLogic::byLogicalId($location['id'], 'gsl');
			if (!is_object($eqLogic)) {
				$eqLogic = new gsl();
				$eqLogic->setName($location['name']);
				$eqLogic->setLogicalId($location['id']);
				$eqLogic->setEqType_name('gsl');
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				if ($location['id'] !== 'global') {
					$eqLogic->setConfiguration('isVisibleGlobal', 1);
				}
				$eqLogic->setConfiguration('isVisiblePanel', 1);
				$eqLogic->save();
			}
			$changed = false;
			$timestamp = date("Y-m-d H:i:s", $location['timestamp'] / 1000);
			$changed = $eqLogic->checkAndUpdateCmd('name', $location['name']) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('coordinated', $location['coordinated'], $timestamp) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('image', $location['image']) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('address', $location['address'], $timestamp) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('battery', $location['battery'], $timestamp) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('charging', $location['charging'], $timestamp) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('accuracy', $location['accuracy'], $timestamp) || $changed;
			$cmdgeoloc = $eqLogic->getConfiguration('cmdgeoloc', null);
			if ($cmdgeoloc !== null) {
				$cmdUpdate = cmd::byId(str_replace('#', '', $cmdgeoloc));
				$cmdUpdate->event($location['coordinated']);
				$cmdUpdate->getEqLogic()->refreshWidget();
			}
			if ($changed) {
				$gChange = true;
				$eqLogic->refreshWidget();
			}
		}
		if ($gChange) {
			$eqLogic = eqLogic::byLogicalId('global', 'gsl');
			if (is_object($eqLogic)) {
				$eqLogic->updateDistance();
				$eqLogic->refreshWidget();
			}
		}
	}

	public static function createGlobalEqLogic() {
		$eqLogic = eqLogic::byLogicalId('global', 'gsl');
		if (!is_object($eqLogic)) {
			$eqLogic = new gsl();
			$eqLogic->setName('Global');
			$eqLogic->setLogicalId('global');
			$eqLogic->setEqType_name('gsl');
			$eqLogic->setIsVisible(1);
			$eqLogic->setIsEnable(1);
			$eqLogic->save();
		}
	}

	public static function saveEqLogicsAfterUpdate() {
	    $eqLogics = self::byType('gsl');
	    foreach ($eqLogics as $eqLogic) {
            $eqLogic->save();
        }
    }

	/*     * *********************Méthodes d'instance************************* */

	public function preInsert() {
		$this->setConfiguration('isVisiblePanel', 1);
		$this->setConfiguration('isVisibleGlobal', 1);
		if ($this->getLogicalId() == '') {
			$this->setConfiguration('type', 'fix');
			$this->setConfiguration('isVisiblePanel', 0);
			$this->setConfiguration('isVisibleGlobal', 0);
		}
	}

	public function preSave() {
		if ($this->getDisplay('height') == 'auto') {
			$this->setDisplay('height', '270px');
		}
		if ($this->getDisplay('width') == 'auto') {
			$this->setDisplay('width', '370px');
		}
	}

	public function postSave() {
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new gslCmd();
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save();
		if ($this->getLogicalId() == 'global') {
			$this->buildDistanceCmd();
			return;
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

		if ($this->getConfiguration('type') == 'fix') {
			$cmd = $this->getCmd(null, 'coordinated');
			$cmd->event($this->getConfiguration('coordinated'));
		} else {
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
			$cmd = $this->getCmd(null, 'battery');
			if (!is_object($cmd)) {
				$cmd = new cmd();
				$cmd->setName('batterie');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('battery');
				$cmd->setType('info');
				$cmd->setSubType('string');
				$cmd->save();
			}
			$cmd = $this->getCmd(null, 'charging');
			if (!is_object($cmd)) {
				$cmd = new cmd();
				$cmd->setName('charge');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('charging');
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->save();
			}
			$cmd = $this->getCmd(null, 'accuracy');
			if (!is_object($cmd)) {
				$cmd = new cmd();
				$cmd->setName('precision');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('accuracy');
				$cmd->setType('info');
				$cmd->setSubType('string');
				$cmd->save();
			}
		}
	}

	public function buildDistanceCmd() {
		$distances = array();
		$eqLogics = self::byType('gsl');
		if (count($eqLogics) > 2) {
			foreach ($eqLogics as $eqLogic1) {
				if ($eqLogic1->getLogicalId() == 'global') {
					continue;
				}
				foreach ($eqLogics as $eqLogic2) {
					if ($eqLogic2->getLogicalId() == 'global') {
						continue;
					}
					if ($eqLogic1->getId() == $eqLogic2->getId()) {
						continue;
					}
					if (isset($distances[$eqLogic1->getId() . '-' . $eqLogic2->getId()]) || isset($distances[$eqLogic2->getId() . '-' . $eqLogic1->getId()])) {
						continue;
					}
					if (!is_object($eqLogic1->getCmd(null, 'coordinated')) || !is_object($eqLogic2->getCmd(null, 'coordinated'))) {
						continue;
					}
					$distances[$eqLogic1->getId() . '-' . $eqLogic2->getId()] = array('eq1' => $eqLogic1, 'eq2' => $eqLogic2);
				}
			}
			foreach ($distances as $value) {
				$cmd = $this->getCmd(null, $value['eq1']->getId() . '-' . $value['eq2']->getId());
				if (!is_object($cmd)) {
					$cmd = $this->getCmd(null, $value['eq2']->getId() . '-' . $value['eq1']->getId());
				}
				if (!is_object($cmd)) {
					$cmd = new cmd();
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId($value['eq1']->getId() . '-' . $value['eq2']->getId());
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setConfiguration('type', 'distances');
				}
				$cmd->setConfiguration('coordinated1', $value['eq1']->getCmd(null, 'coordinated')->getId());
				$cmd->setConfiguration('coordinated2', $value['eq2']->getCmd(null, 'coordinated')->getId());
				$cmd->setName('Distance ' . $value['eq1']->getName() . ' ' . $value['eq2']->getName());
				$cmd->save();
			}
		}
	}

	public function updateDistance() {
		if ($this->getLogicalId() != 'global') {
			return;
		}
		$coordinated = array();
		foreach ($this->getCmd('info') as $cmd) {
			if ($cmd->getConfiguration('type') != 'distances') {
				continue;
			}
			if (!isset($coordinated[$cmd->getConfiguration('coordinated1')])) {
				$coordinated[$cmd->getConfiguration('coordinated1')] = cmd::cmdToValue('#' . $cmd->getConfiguration('coordinated1') . '#');
			}
			if (!isset($coordinated[$cmd->getConfiguration('coordinated2')])) {
				$coordinated[$cmd->getConfiguration('coordinated2')] = cmd::cmdToValue('#' . $cmd->getConfiguration('coordinated2') . '#');
			}
			if (strpos($coordinated[$cmd->getConfiguration('coordinated1')], '#') !== false || strpos($coordinated[$cmd->getConfiguration('coordinated2')], '#') !== false) {
				continue;
			}
			if ($coordinated[$cmd->getConfiguration('coordinated1')] == '' || $coordinated[$cmd->getConfiguration('coordinated2')] == '') {
				continue;
			}
			$cmd->event(self::distance($coordinated[$cmd->getConfiguration('coordinated1')], $coordinated[$cmd->getConfiguration('coordinated2')]));
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
		$refresh = $this->getCmd(null, 'refresh');
		if (is_object($refresh)) {
			$replace['#refresh_id#'] = $refresh->getId();
		}
		if ($this->getLogicalId() == 'global') {
			$replace['#adresses#'] = '';
			$data = array();
			$eqLogics = self::byType('gsl', true);
			foreach ($eqLogics as $eqLogic) {
				$color = '#ffffff';
				if ($eqLogic->getLogicalId() == 'global') {
					continue;
				}
				if (!$eqLogic->getConfiguration('isVisibleGlobal', 0)) {
					continue;
				}
				if ($eqLogic->getConfiguration('type') == 'fix') {
					$color = $eqLogic->getConfiguration('color');
				}
				$data[$eqLogic->getId()] = $eqLogic->buildLocation();
				$data[$eqLogic->getId()]['color'] = $color;
				$replace['#adresses#'] .= '<div class="gsl-address" id="gsl-address-' . $this->getLogicalId() . '-' . $eqLogic->getId() . '">';
				$replace['#adresses#'] .= '<span class="pull-right" style="text-align: center;"><img style="border: 2px solid white; background-color:' . $color . ';cursor:pointer; margin-top:5px;width:50px; height:50px;border-radius: 50% !important;" src="' . $data[$eqLogic->getId()]['image'] . '" />';
        if(isset($data[$eqLogic->getId()]['battery']) && $data[$eqLogic->getId()]['battery'] != '') {
            $replace['#adresses#'] .= '<br/><span style="font-size:0.7em;">'.($data[$eqLogic->getId()]['charging'] ? '<i class="fas fa-bolt"></i> ' : '' ).'<i class="fa ' . $data[$eqLogic->getId()]['battery_icon'] . '"></i> ' . $data[$eqLogic->getId()]['battery'] . '%</span>';
        }
        $replace['#adresses#'] .= '</span>';
				$replace['#adresses#'] .= '<span style="font-size:0.8em;">' . $data[$eqLogic->getId()]['name'] . '</span><br/>';
				$replace['#adresses#'] .= '<span>' . $data[$eqLogic->getId()]['address'] . '</span><br/>';
				$replace['#adresses#'] .= '<span style="font-size:0.7em;">' . $data[$eqLogic->getId()]['horodatage'] . '</span><br/>';
				$replace['#adresses#'] .= '<span style="font-size:0.7em;">Précision : ' . $data[$eqLogic->getId()]['accuracy'] . 'm</span>';
				$replace['#adresses#'] .= '</div>';
				$replace['#adresses#'] .= '<hr/>';
			}
			$replace['#json#'] = str_replace("'", "\'", json_encode($data));
			$replace['#height-map#'] = ($version == 'dashboard') ? $replace['#height#'] - 60 : 170;
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl_global', 'gsl')));
		} else {
			$color = '#ffffff';
			if ($this->getConfiguration('type') == 'fix') {
				$color = $this->getConfiguration('color');
			}
			$data = array($this->getId() => $this->buildLocation());
			$data[$this->getId()]['color'] = $color;
			$replace['#adresses#'] = '<span>' . $data[$this->getId()]['address'] . '</span><br/>';
			if(isset($data[$this->getId()]['battery']) && $data[$this->getId()]['battery'] != '') {
                $replace['#adresses#'] .= '<span style="font-size:0.7em;">'.($data[$this->getId()]['charging'] ? '<i class="fas fa-bolt"></i> ' : '' ).'<i class="fa ' . $data[$this->getId()]['battery_icon'] . '"></i> ' . $data[$this->getId()]['battery'] . '%</span> - ';
            }
			$replace['#adresses#'] .= '<span style="font-size:0.7em;">' . $data[$this->getId()]['horodatage'] . '</span><br/>';
				$replace['#adresses#'] .= '<span style="font-size:0.7em;">Précision : ' . $data[$this->getId()]['accuracy'] . 'm</span>';
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
			'image' => 'plugins/gsl/3rparty/images/avatar.png',
			'name' => $this->getName(),
		);
		$cmds = $this->getCmd('info');
		foreach ($cmds as $cmd) {
			$return[$cmd->getLogicalId()] = $cmd->execCmd();
			if ($cmd->getLogicalId() == 'battery') {
				$icon = 'fa-battery-0';
				$battery = $return[$cmd->getLogicalId()];
				if($battery > 80){
					$icon = 'fa-battery-4';
				}else if($battery > 60){
					$icon = 'fa-battery-3';
				}else if($battery > 40){
					$icon = 'fa-battery-2';
				}else if($battery > 20){
					$icon = 'fa-battery-1';
				}
				$return['battery_icon'] = $icon;
			}
			if ($cmd->getLogicalId() != 'address') {
				continue;
			}
			$timestamp = $cmd->getCollectDate();
			if (!$timestamp) {
				continue;
			}
			$return['horodatage'] = "le " . date("d/m/Y à H:i", strtotime($timestamp));
		}
		return $return;
	}
  
	public function postUpdate() {
		if (file_exists(jeedom::getTmpFolder('gsl') . '/cookies.txt') && !file_exists(self::$_cookiePath)) {
          	copy(jeedom::getTmpFolder('gsl') . '/cookies.txt', self::$_cookiePath);
          	unlink(jeedom::getTmpFolder('gsl') . '/cookies.txt');
		}
   	}

	/*     * **********************Getteur Setteur*************************** */
}

class gslCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {
		if ($this->getLogicalId() == 'refresh') {
			gsl::pull(true);
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}
