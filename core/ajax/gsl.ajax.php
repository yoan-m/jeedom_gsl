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

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	ajax::init();

	if (init('action') == 'getLocations') {
		if (init('logicalId') == 'global') {
			$eqLogics = eqLogic::byType('gsl', true);
		} else {
			$eqLogics = array(eqLogic::byLogicalId(init('logicalId'), 'gsl'));
		}
		if (count($eqLogics) === 0) {
			throw new Exception(__('Aucun equipement', __FILE__));
		}
		$return = array();
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getLogicalId() == 'global') {
				continue;
			}
			if (!$eqLogic->getIsVisible()) {
				continue;
			}
			$loc = array();
			$cmds = $eqLogic->getCmd();
			foreach ($cmds as $cmd) {
				if ($cmd->getConfiguration('type') == 'command') {
					continue;
				}
				$loc[$cmd->getName()] = $cmd->execCmd();
				if ($cmd->getName() == 'timestamp') {
					$timestamp = $cmd->execCmd();
					if (!$timestamp) {
						continue;
					}
					$timestamp = (time() - ($timestamp / 1000));
					if ($timestamp <= 60) {
						$loc['horodatage'] = 'à l\'instant';
					} else if ($timestamp < 3600) {
						$loc['horodatage'] = 'il y a ' . intval(($timestamp) / 60) . ' minutes';
					} else {
						$loc['horodatage'] = 'il y a ' . intval((($timestamp) / 60) / 60) . ' heures';
					}
				}
			}
			$loc['id'] = $eqLogic->getLogicalId();
			$return[] = $loc;
		}
		ajax::success($return);
	}

	if (init('action') == 'createGlobalEqLogic') {
		gsl::createGlobalEqLogic();
		ajax::success();
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}
