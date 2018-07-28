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
		if(init('logicalId') == 'global'){
			$equipements = eqLogic::byType('gsl', true);
		}else{
			$equipements = [];
			array_push($equipements, eqLogic::byLogicalId(init('logicalId'), 'gsl'));
		}
		if(count($equipements) === 0){
			ajax::error('Aucun equipement', 0);
		}
		$return = array();
		foreach ($equipements as $eq) {

			if($eq->getLogicalId() == 'global'){
				continue;
			}
			if(!$eq->getIsVisible()){
				continue;
			}
			$loc = array();
			$cmds = $eq->getCmd();
			foreach ($cmds as $cmd) {
				if ($cmd->getConfiguration('type') == "command") {
					continue;
				}
				$loc[$cmd->getName()] = $cmd->execCmd();	
				if($cmd->getName() == 'timestamp'){
					$now = time();
					$timestamp = $cmd->execCmd();
					if($timestamp){
						$timestamp = $timestamp/1000;
						$timestamp  = ($now - $timestamp);
						if($timestamp <=60){
							$loc['horodatage'] = 'à l\'instant';
						}else if($timestamp <3600){
							$loc['horodatage'] = 'il y a '.intval(($timestamp)/60).' minutes';
						}else{
							$loc['horodatage'] = 'il y a '.intval((($timestamp)/60)/60).' heures';
						}
					}
				}						
			}
			$loc['id'] = $eq->getLogicalId();		
			array_push($return,$loc);
		}
		ajax::success($return);		
	}else if (init('action') == 'createGlobalEqLogic') {
		gsl::createGlobalEqLogic();
		ajax::success();	
	}	

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}

