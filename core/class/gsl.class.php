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
        $url = 'https://www.google.com/maps/preview/locationsharing/read?authuser='.config::byKey('authuser', 'gsl', '0').'&pb=';
        log::add('gsl', 'debug', __('google_callLocationUrl '.$url, __FILE__));
        $ch = curl_init($url);
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
            if (!isset($result[9])) {
                throw new Exception(__('Erreur données de localisation invalide ou vide : ', __FILE__) . json_encode($result));
            }
            $result[9][0] = array(1,null,null,'Mon compte');
            $result[9][13] = array(null,null);
            log::add('gsl', 'debug', __('Location data : Connection réussie, reponse : ', __FILE__) .json_encode(array(array($result[9]))));
            return array(array($result[9]));
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
                'battery' => (isset($user[13]) && isset($user[13][1]) ? $user[13][1] : null),
                'charging' => (isset($user[13]) && isset($user[13][0]) ? $user[13][0] : null),
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
                //$cmdUpdate->getEqLogic()->refreshWidget();
            }
            if ($changed) {
                $gChange = true;
                //$eqLogic->refreshWidget();
            }
        }
        if ($gChange) {
            $eqLogic = eqLogic::byLogicalId('global', 'gsl');
            if (is_object($eqLogic)) {
                $eqLogic->updateDistance();
                //$eqLogic->refreshWidget();
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

    public static function getMapLayers(){
        return array(
			'CartoDB.DarkMatter'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
            'CartoDB.DarkMatterNoLabels'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
            'CartoDB.Positron'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
            'CartoDB.PositronNoLabels'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
			'CartoDB.Voyager'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
            'CartoDB.VoyagerLabelsUnder'=>array('url'=>'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
            'CartoDB.VoyagerNoLabels'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
            'Esri.DeLorme'=>array('minZoom'=>1,'maxZoom'=>11,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/Specialty/DeLorme_World_Base_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Copyright: &copy;2012 DeLorme'),
            'Esri.NatGeoWorldMap'=>array('maxZoom'=>16,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; National Geographic, Esri, DeLorme, NAVTEQ, UNEP-WCMC, USGS, NASA, ESA, METI, NRCAN, GEBCO, NOAA, iPC'),
            'Esri.OceanBasemap'=>array('maxZoom'=>13,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Sources: GEBCO, NOAA, CHS, OSU, UNH, CSUMB, National Geographic, DeLorme, NAVTEQ, and Esri'),
            'Esri.WorldGrayCanvas'=>array('maxZoom'=>16,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ'),
            'Esri.WorldImagery'=>array('url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'),
            'Esri.WorldPhysical'=>array('maxZoom'=>8,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Physical_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: US National Park Service'),
            'Esri.WorldShadedRelief'=>array('maxZoom'=>13,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: Esri'),
            'Esri.WorldStreetMap'=>array('url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012'),
            'Esri.WorldTerrain'=>array('maxZoom'=>13,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: USGS, Esri, TANA, DeLorme, and NPS'),
            'Esri.WorldTopoMap'=>array('url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community'),
            'OpenStreetMap.BZH'=>array('maxZoom'=>19,'url'=>'https://tile.openstreetmap.bzh/br/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, Tiles courtesy of <a href=\"http://www.openstreetmap.bzh/\" target=\"_blank\">Breton OpenStreetMap Team</a>'),
            'OpenStreetMap.DE'=>array('maxZoom'=>18,'url'=>'https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'OpenStreetMap.France'=>array('maxZoom'=>20,'url'=>'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png','attribution'=>'&copy; Openstreetmap France | &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'OpenStreetMap.HOT'=>array('maxZoom'=>19,'url'=>'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, Tiles style by <a href=\"https://www.hotosm.org/\" target=\"_blank\">Humanitarian OpenStreetMap Team</a> hosted by <a href=\"https://openstreetmap.fr/\" target=\"_blank\">OpenStreetMap France</a>'),
			'OpenStreetMap.Mapnik'=>array('maxZoom'=>19,'url'=>'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'OpenTopoMap'=>array('maxZoom'=>17,'url'=>'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png','attribution'=>'Map data: &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, <a href=\"http://viewfinderpanoramas.org\">SRTM</a> | Map style: &copy; <a href=\"https://opentopomap.org\">OpenTopoMap</a> (<a href=\"https://creativecommons.org/licenses/by-sa/3.0/\">CC-BY-SA</a>)'),
            //'Stadia.AlidadeSmooth'=>array('maxZoom'=>20,'url'=>'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png','attribution'=>'&copy; <a href=\"https://stadiamaps.com/\">Stadia Maps</a>, &copy; <a href=\"https://openmaptiles.org/\">OpenMapTiles</a> &copy; <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors'),
            //'Stadia.AlidadeSmoothDark'=>array('maxZoom'=>20,'url'=>'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png','attribution'=>'&copy; <a href=\"https://stadiamaps.com/\">Stadia Maps</a>, &copy; <a href=\"https://openmaptiles.org/\">OpenMapTiles</a> &copy; <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors'),
            //'Stadia.OSMBright'=>array('maxZoom'=>20,'url'=>'https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}{r}.png','attribution'=>'&copy; <a href=\"https://stadiamaps.com/\">Stadia Maps</a>, &copy; <a href=\"https://openmaptiles.org/\">OpenMapTiles</a> &copy; <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors'),
            //'Stadia.Outdoors'=>array('maxZoom'=>20,'url'=>'https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}{r}.png','attribution'=>'&copy; <a href=\"https://stadiamaps.com/\">Stadia Maps</a>, &copy; <a href=\"https://openmaptiles.org/\">OpenMapTiles</a> &copy; <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors'),
            'Stamen.Terrain'=>array('minZoom'=>0,'maxZoom'=>18,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'Stamen.TerrainBackground'=>array('minZoom'=>0,'maxZoom'=>18,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/terrain-background/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
			'Stamen.Toner'=>array('minZoom'=>0,'maxZoom'=>20,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'Stamen.TonerBackground'=>array('minZoom'=>0,'maxZoom'=>20,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/toner-background/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'Stamen.TonerLite'=>array('minZoom'=>0,'maxZoom'=>20,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'Stamen.Watercolor'=>array('minZoom'=>1,'maxZoom'=>16,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.{ext}','subdomains'=>'abcd','ext'=>'jpg','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
            'Wikimedia'=>array('minZoom'=>1,'maxZoom'=>19,'url'=>'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png','attribution'=>'<a href=\"https://wikimediafoundation.org/wiki/Maps_Terms_of_Use\">Wikimedia</a'));
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
            if($this->getConfiguration('coordinatesType') == 'jeedom'){
                $cmd = $this->getCmd(null, 'coordinated');
                $cmd->event(config::byKey('info::latitude').','.config::byKey('info::longitude'));
            }else{
                $cmd = $this->getCmd(null, 'coordinated');
                $cmd->event($this->getConfiguration('coordinated'));
            }
            $this->buildDistanceCmd();
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
                    if($eqLogic2->getConfiguration('type') == 'fix' && $eqLogic2->getConfiguration('coordinatesType') == 'jeedom'){
                        $cmd = $eqLogic2->getCmd(null, 'coordinated');
                        $cmd->event(config::byKey('info::latitude').','.config::byKey('info::longitude'));
                    }
                    if($eqLogic1->getConfiguration('type') == 'fix' && $eqLogic1->getConfiguration('coordinatesType') == 'jeedom'){
                        $cmd = $eqLogic1->getCmd(null, 'coordinated');
                        $cmd->event(config::byKey('info::latitude').','.config::byKey('info::longitude'));
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

        if($_version == 'dview'){
            $replace['#width#'] = '100%';
            //$replace['#height#'] = '100%';
        }

        $refresh = $this->getCmd(null, 'refresh');
        if (is_object($refresh)) {
            $replace['#refresh_id#'] = $refresh->getId();
        }
        $data = array('points'=>array());
        if(array_key_exists(config::byKey('light-theme', 'gsl', ''), self::getMapLayers())){
            $data['light-theme'] = self::getMapLayers()[config::byKey('light-theme', 'gsl', 'OpenStreetMap.Mapnik')];
        }else{
            $data['light-theme'] = 'OpenStreetMap.Mapnik';
        }
        if(array_key_exists(config::byKey('dark-theme', 'gsl', ''), self::getMapLayers())){
            $data['dark-theme'] = self::getMapLayers()[config::byKey('dark-theme', 'gsl', 'OpenStreetMap.Mapnik')];
        }else{
            $data['dark-theme'] = 'OpenStreetMap.Mapnik';
        }
        $data['control-zoom'] = (bool)config::byKey('control-zoom', 'gsl', true);
        $data['control-attributions'] = (bool)config::byKey('control-attributions', 'gsl', true);
        if ($this->getLogicalId() == 'global') {
            $replace['#adresses#'] = '<div id="gsl-address-global-'.$this->getId().'">';
            $eqLogics = self::byType('gsl', true);
            foreach ($eqLogics as $eqLogic) {
                $color = '#ffffff';
                if ($eqLogic->getLogicalId() == 'global') {
                    continue;
                }
                if (!$eqLogic->getConfiguration('isVisibleGlobal', 0)) {
                    continue;
                }
                if ($eqLogic->getConfiguration('color')) {
                    $color = $eqLogic->getConfiguration('color');
                }
                $data['points'][$eqLogic->getId()] = $eqLogic->buildLocation();
                $data['points'][$eqLogic->getId()]['color'] = $color;
                $replace['#adresses#'] .= '<div class="gsl-address" id="gsl-address-' . $this->getLogicalId() . '-' . $eqLogic->getId() . '">';
				$replace['#adresses#'] .= '<span class="pull-right" style="text-align: center;">';
				if(isset($data['points'][$eqLogic->getId()]['image']) && isset($data['points'][$eqLogic->getId()]['image']['value']) && $data['points'][$eqLogic->getId()]['image']['value'] != ''){
					$replace['#adresses#'] .= '<img style="border: 2px solid white; background-color:' . $color . ';cursor:pointer; margin-top:5px;width:50px; height:50px;border-radius: 50% !important;" src="' . $data['points'][$eqLogic->getId()]['image']['value'] . '" />';
				}else{
					$replace['#adresses#'] .= '<div style="border: 2px solid white; background-color:' . $color . ';cursor:pointer; margin-top:5px;width:50px; height:50px;border-radius: 50% !important;"></div>';
				}
				if(isset($data['points'][$eqLogic->getId()]['battery']) && isset($data['points'][$eqLogic->getId()]['battery']['value']) && $data['points'][$eqLogic->getId()]['battery']['value'] != ''){
					$replace['#adresses#'] .= '<br/><span class="gsl-battery">';
					if(isset($data['points'][$eqLogic->getId()]['charging']) && isset($data['points'][$eqLogic->getId()]['charging']['value']) && $data['points'][$eqLogic->getId()]['charging']['value'] != ''){
						$replace['#adresses#'] .= '<span class="cmd gsl-battery" data-cmd_id="'.$data['points'][$eqLogic->getId()]['charging']['id'].'"><i></i></span> ';
					}
					$replace['#adresses#'] .= '<span class="cmd gsl-battery-icon" data-cmd_id="'.$data['points'][$eqLogic->getId()]['battery']['id'].'"><i></i></span> <span class="cmd gsl-battery" data-cmd_id="'.$data['points'][$eqLogic->getId()]['battery']['id'].'"></span>%</span>';
				}
				$replace['#adresses#'] .= '</span>';
                $replace['#adresses#'] .= '<span class="gsl-name">' . $data['points'][$eqLogic->getId()]['name']['value'] . '</span><br/>';
                $replace['#adresses#'] .= '<span class="cmd gsl-address" data-cmd_id="'.$data['points'][$eqLogic->getId()]['address']['id'].'"></span><br/>';
                $replace['#adresses#'] .= '<span class="cmd" data-cmd_id="'.$data['points'][$eqLogic->getId()]['coordinated']['id'].'"></span>';
                $replace['#adresses#'] .= '<span class="cmd gsl-horodatage" data-cmd_id="'.$data['points'][$eqLogic->getId()]['address']['id'].'"></span><br/>';
                $replace['#adresses#'] .= '<span class="cmd gsl-precision" data-cmd_id="'.$data['points'][$eqLogic->getId()]['accuracy']['id'].'"></span>';
                $replace['#adresses#'] .= '</div>';
                $replace['#adresses#'] .= '<hr/>';
            }
            $replace['#adresses#'] .= '</div>';
            $replace['#json#'] = str_replace("'", "\'", json_encode($data));
            $replace['#height-map#'] = ($version == 'dashboard') ? intval($replace['#height#']) - 60 : 170;
            return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl_global', 'gsl')));
        } else {
            $color = '#ffffff';
            if ($this->getConfiguration('color')) {
                $color = $this->getConfiguration('color');
            }
            $data['points'][$this->getId()] = $this->buildLocation();
            $data['points'][$this->getId()]['color'] = $color;
            $replace['#adresses#'] = '<div id="gsl-address-'.$this->getId().'"><span class="cmd gsl-address" data-cmd_id="'.$data['points'][$this->getId()]['address']['id'].'"></span><span class="cmd" data-cmd_id="'.$data['points'][$this->getId()]['coordinated']['id'].'"></span><br/>';
            if(isset($data['points'][$this->getId()]['battery']) && isset($data['points'][$this->getId()]['battery']['value']) && $data['points'][$this->getId()]['battery']['value'] != '') {
                $replace['#adresses#'] .= '<span class="gsl-battery">';
                if(isset($data['points'][$this->getId()]['charging']) && isset($data['points'][$this->getId()]['charging']['value']) && $data['points'][$this->getId()]['charging']['value'] != ''){
                    $replace['#adresses#'] .= '<span class="cmd gsl-battery" data-cmd_id="'.$data['points'][$this->getId()]['charging']['id'].'"><i></i></span> ';
                }
                $replace['#adresses#'] .= '<span class="cmd gsl-battery-icon" data-cmd_id="'.$data['points'][$this->getId()]['battery']['id'].'"><i></i></span> <span class="cmd gsl-battery" data-cmd_id="'.$data['points'][$this->getId()]['battery']['id'].'"></span>%</span> - ';
            }
            $replace['#adresses#'] .= '<span class="cmd gsl-horodatage" data-cmd_id="'.$data['points'][$this->getId()]['address']['id'].'"></span><br/>';
            if($data['points'][$this->getId()]['accuracy'] && $data['points'][$this->getId()]['accuracy']['value']){
                $replace['#adresses#'] .= '<span class="cmd gsl-precision" data-cmd_id="'.$data['points'][$this->getId()]['accuracy']['id'].'"></span>';
            }
            $replace['#adresses#'] .= '</div>';
            $replace['#json#'] = str_replace("'", "\'", json_encode($data));
            $replace['#height-map#'] = ($version == 'dashboard') ? intval($replace['#height#']) - 100 : 170;
            return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'gsl', 'gsl')));
        }
    }

    public function buildLocation() {
        if ($this->getLogicalId() == 'global') {
            return;
        }
        $return = array(
            'id' => $this->getLogicalId(),
            'image' => array('value'=>'plugins/gsl/3rparty/images/avatar.png'),
            'name' => array('value'=>$this->getName()),
            'fix'=>  $this->getConfiguration('type') == 'fix'
        );
        $cmds = $this->getCmd('info');
        foreach ($cmds as $cmd) {
			if ($cmd->getLogicalId() == 'name') {
                continue;
            }
            if ($cmd->getLogicalId() == 'coordinated') {
                if($this->getConfiguration('coordinatesType') == 'jeedom'){
                    $return[$cmd->getLogicalId()] = array('id'=>$cmd->getId(), 'value'=>config::byKey('info::latitude').','.config::byKey('info::longitude'));
                }else{
                    $return[$cmd->getLogicalId()] = array('id'=>$cmd->getId(), 'value'=>$cmd->execCmd());
                }
                continue;
            }
            $return[$cmd->getLogicalId()] = array('id'=>$cmd->getId(), 'value'=>$cmd->execCmd());
            if ($cmd->getLogicalId() != 'address') {
                continue;
            }
            $timestamp = $cmd->getCollectDate();
            if (!$timestamp) {
                continue;
            }
            $return[$cmd->getLogicalId()]['collectDate']=$timestamp;
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
