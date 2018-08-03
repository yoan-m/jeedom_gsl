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

	public static function google_callLocationUrl() {
		$ch = curl_init('https://www.google.com/maps/preview/locationsharing/read?authuser=0&pb=');
		curl_setopt($ch, CURLOPT_COOKIEJAR, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		log::add('gsl', 'debug', __('Location data : Connection réussie, reponse : ', __FILE__) . $info['http_code']);
		if (empty($info['http_code']) || $info['http_code'] != 200) {
			throw new Exception(__('Erreur données de localisation code retour invalide : ', __FILE__) . $info['http_code']);
		}
		$result = substr($response, 4);
		if (!is_json($result)) {
			throw new Exception(__('Erreur données de localisation n\'est pas un json valide : ', __FILE__) . $result);
		}
		$result = json_decode($result, true);
		if (!isset($result[0])) {
			throw new Exception(__('Erreur données de localisation invalide ou vide : ', __FILE__) . json_encode($result));
		}
		return $result;
	}

	public static function google_locationData() {
		try {
			$result = self::google_callLocationUrl();
		} catch (Exception $e) {
			self::google_logout();
			self::google_connect();
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
			);
		}
		return $return;
	}

	public static function google_logout() {
		unlink(jeedom::getTmpFolder('gsl') . '/cookies.txt');
	}

	public static function google_connect() {
		$data = array();
		/*************************STAGE 1*******************************/
		log::add('gsl', 'debug', __('Stage 1 : Connection à google', __FILE__));
		$ch = curl_init('https://accounts.google.com/ServiceLogin?rip=1&nojavascript=1');
		curl_setopt($ch, CURLOPT_COOKIEJAR, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		log::add('gsl', 'debug', __('Stage 1 : Connection réussie, reponse : ', __FILE__) . $info['http_code']);
		if (!empty($info['http_code']) && $info['http_code'] == 302) {
			return true;
		}
		if (empty($info['http_code']) || $info['http_code'] != 200) {
			throw new Exception(__('Erreur stage 1 : code retour invalide : ', __FILE__) . $info['http_code']);
		}
		curl_close($ch);
		$headers = self::get_headers_from_curl_response($response);
		if (!isset($headers['Set-Cookie'])) {
			throw new Exception(__('Erreur stage 1 : aucun cookie', __FILE__));
		}
		$data['cookie'] = array('google.com' => self::processCookies($headers['Set-Cookie']));
		preg_match_all('/<input type="hidden" name="gxf" value="(.*?)">/m', $response, $matches);
		if (!isset($matches[1]) || !isset($matches[1][0])) {
			throw new Exception(__('Erreur stage 1 : champs gfx non trouvé', __FILE__));
		}
		$data['gfx'] = $matches[1][0];
		log::add('gsl', 'debug', __('Stage 1 : Connection réussie, sauvegarde du cookie', __FILE__));

		/*************************STAGE 2*******************************/
		log::add('gsl', 'debug', __('Stage 2 : envoi du mail', __FILE__));
		$ch = curl_init("https://accounts.google.com/signin/v1/lookup");
		curl_setopt($ch, CURLOPT_COOKIEJAR, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie' => 'GAPS=' . $data['cookie']['google.com']['GAPS']));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'Page' => 'PasswordSeparationSignIn',
			'gxf' => $data['gfx'],
			'rip' => '1',
			'ProfileInformation' => '',
			'SessionState' => '',
			'bgresponse' => 'js_disabled',
			'pstMsg' => '0',
			'checkConnection' => '',
			'checkedDomains' => 'youtube',
			'Email' => config::byKey('google_user', 'gsl'),
			'identifiertoken' => '',
			'identifiertoken_audio' => '',
			'identifier-captcha-input' => '',
			'signIn' => 'Weiter',
			'Passwd' => '',
			'PersistentCookie' => 'yes',
		));
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		log::add('gsl', 'debug', __('Stage 2 : Connection réussie, reponse : ', __FILE__) . $info['http_code']);
		if (empty($info['http_code']) || $info['http_code'] != 200) {
			throw new Exception(__('Erreur stage 2 : code retour invalide : ', __FILE__) . $info['http_code']);
		}
		$headers = self::get_headers_from_curl_response($response);
		if (!isset($headers['Set-Cookie'])) {
			throw new Exception(__('Erreur stage 2 : aucun cookie', __FILE__));
		}
		$data['cookie'] = array('google.com' => array_merge($data['cookie']['google.com'], self::processCookies($headers['Set-Cookie'], $data['cookie'])));

		preg_match_all('/<input id="profile-information" name="ProfileInformation" type="hidden" value="(.*?)">/m', $response, $matches);
		if (!isset($matches[1]) || !isset($matches[1][0])) {
			throw new Exception(__('Erreur stage 2 : champs ProfileInformation non trouvé', __FILE__));
		}
		$data['ProfileInformation'] = $matches[1][0];

		preg_match_all('/<input id="session-state" name="SessionState" type="hidden" value="(.*?)">/m', $response, $matches);
		if (!isset($matches[1]) || !isset($matches[1][0])) {
			throw new Exception(__('Erreur stage 2 : champs SessionState non trouvé', __FILE__));
		}
		$data['SessionState'] = $matches[1][0];
		log::add('gsl', 'debug', __('Stage 2 : Connection réussie, sauvegarde du cookie', __FILE__));

		/*************************STAGE 3*******************************/
		log::add('gsl', 'debug', __('Stage 3 : envoi du mot de passe', __FILE__));

		$ch = curl_init("https://accounts.google.com/signin/challenge/sl/password");
		curl_setopt($ch, CURLOPT_COOKIEJAR, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, jeedom::getTmpFolder('gsl') . '/cookies.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Cookie' => 'GAPS=' . $data['cookie']['google.com']['GAPS'] . ';GALX=' . $data['cookie']['google.com']['GALX'],
			'Origin' => 'https://accounts.google.com',
			'Referer' => 'https://accounts.google.com/signin/v1/lookup',
			'Upgrade-Insecure-Requests' => '1',
		));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'Page' => 'PasswordSeparationSignIn',
			'GALX' => $data['cookie']['google.com']['GALX'],
			'gxf' => $data['gfx'],
			'checkedDomains' => 'youtube',
			'pstMsg' => '0',
			'rip' => '1',
			'ProfileInformation' => $data['ProfileInformation'],
			'SessionState' => $data['SessionState'],
			'_utf8' => '☃',
			'bgresponse' => 'js_disabled',
			'checkConnection' => '',
			'Email' => config::byKey('google_user', 'gsl'),
			'signIn' => 'Weiter',
			'Passwd' => config::byKey('google_password', 'gsl'),
			'PersistentCookie' => 'yes',
			'rmShown' => '1',
		));
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		log::add('gsl', 'debug', 'Stage 3 : Connection réussie, reponse : ' . $info['http_code']);
		if (empty($info['http_code']) || $info['http_code'] != 302) {
			throw new Exception(__('Erreur stage 3 : connection etablie mais echec de l\'autentification, code 302 attendu : ', __FILE__) . $info['http_code']);
		}
		$headers = self::get_headers_from_curl_response($response);
		if (!isset($headers['Set-Cookie'])) {
			throw new Exception(__('Erreur stage 3 : aucun cookie', __FILE__));
		}
		$data['cookie'] = array('google.com' => array_merge($data['cookie']['google.com'], self::processCookies($headers['Set-Cookie'], $data['cookie'])));
		if (!isset($headers['Location'])) {
			throw new Exception(__('Erreur stage 3 : aucun adresse de redirection', __FILE__));
		}
		return true;
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

	public static function pull($_force = false) {
		if (!$_force) {
			$dateRun = new DateTime();
			$c = new Cron\CronExpression(config::byKey('refresh::frequency', 'gsl', '*/5 * * * *'), new Cron\FieldFactory);
			if (!$c->isDue($dateRun)) {
				return;
			}
			sleep(rand(0, 90));
		}
		$gChange = false;
		foreach (self::google_locationData() as $location) {
			$eqLogic = eqLogic::byLogicalId($location['id'], 'gsl');
			if (!is_object($eqLogic)) {
				$eqLogic = new eqLogic();
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
			$changed = $eqLogic->checkAndUpdateCmd('name', $location['name']) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('coordinated', $location['coordinated'], $location['timestamp']) || $changed;
			$cmdgeoloc = $eqLogic->getConfiguration('cmdgeoloc', null);
			if ($cmdgeoloc !== null) {
				$cmdUpdate = cmd::byId(str_replace('#', '', $cmdgeoloc));
				$cmdUpdate->event($location['coordinated']);
				$cmdUpdate->getEqLogic()->refreshWidget();
			}
			$changed = $eqLogic->checkAndUpdateCmd('image', $location['image']) || $changed;
			$changed = $eqLogic->checkAndUpdateCmd('address', $location['address'], $location['timestamp']) || $changed;
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

	public function preInsert() {
		$this->setConfiguration('isVisiblePanel', 1);
		$this->setConfiguration('isVisibleGlobal', 1);
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
			$refresh = new weatherCmd();
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save();
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
		$refresh = $this->getCmd(null, 'refresh');
		if (is_object($refresh)) {
			$replace['#refresh_id#'] = $refresh->getId();
		}
		if ($this->getLogicalId() == 'global') {
			$replace['#adresses#'] = '';
			$data = array();
			$eqLogics = self::byType('gsl', true);
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getLogicalId() == 'global') {
					continue;
				}
				if (!$eqLogic->getConfiguration('isVisibleGlobal', 0)) {
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
			$replace['#adresses#'] = '<span>' . $data[$this->getId()]['address'] . '</span><br/>';
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
			if ($cmd->getLogicalId() != 'address') {
				continue;
			}
			$timestamp = $cmd->getCollectDate();
			if (!$timestamp) {
				continue;
			}
			$return['horodatage'] = "le " . date("d/m/Y à H:i", $timestamp / 1000);
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
		if ($this->getLogicalId() == 'refresh') {
			gsl::pull(true);
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}
