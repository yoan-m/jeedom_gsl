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
if (maps == undefined) {
	var maps = {};
}
 $('#cmdgeoloc').on('click', function () {
    jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
        $('.eqLogicAttr[data-l2key=cmdgeoloc]').value(result.human);
    });
});
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});


function createMap(_mapId){
	$.ajax({
		type: 'POST',
		url: 'plugins/gsl/core/ajax/gsl.ajax.php',
		global:false,
		data: {
			action: 'getAllLocations',
			id: _mapId,
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			var map = L.map('map', {
				// Set latitude and longitude of the map center (required)
				center: [51.5, -0.09],
				// Set the initial zoom level, values 0-18, where 0 is most zoomed-out (required)
				zoom: 15
			});
			// create the tile layer with correct attribution
			var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
			var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
			var osm = new L.TileLayer(osmUrl, {attribution: osmAttrib}).addTo(map);

			var fg = L.featureGroup();
			  // And lets add it to our map.
			 fg.addTo(map);
			 maps[_mapId] = {};
			 maps[_mapId].map = map;
			 maps[_mapId].fg = fg;
			 for (var i = 0; i < data.result.length; i++) {
				 updateMarker(_mapId, data.result[i]);
			 }
		});
}

function updateMarker(_mapId, loc){
	var icon = L.icon({
		iconUrl: loc.image,
		shadowUrl: '../../3rdparty/images/avatar-pin-2x.png',

		iconSize:     [36, 36], // size of the icon
		shadowSize:   [50, 55], // size of the shadow
		iconAnchor:   [18, 47], // point of the icon which will correspond to marker's location
		shadowAnchor: [25, 55],  // the same for the shadow
		popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
	});
	L.marker(loc.coordonnees.split(','), {icon: icon}).addTo(maps[_mapId].fg);
}
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

$('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=refreshCron]').value(result.value);
    });
});
