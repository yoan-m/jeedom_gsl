var gslObjects;
if(!gslObjects){
    gslObjects = {
        maps: {},
        intervals: {}
    };
}

var GSL_MONTH_NAMES = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
];
function gslGetFormattedDate(date, prefomattedDate = false, hideYear = false) {
    const day = date.getDate();
    const month = GSL_MONTH_NAMES[date.getMonth()];
    const year = date.getFullYear();
    const hours = date.getHours();
    let minutes = date.getMinutes();

    if (minutes < 10) {
        // Adding leading zero to minutes
        minutes = `0${ minutes }`;
    }

    if (prefomattedDate) {
        // Today at 10:20
        // Yesterday at 10:20
        return `${ prefomattedDate } à ${ hours }:${ minutes }`;
    }

    if (hideYear) {
        // 10. January at 10:20
        return `${ day }. ${ month } à ${ hours }:${ minutes }`;
    }

    // 10. January 2017. at 10:20
    return `${ day }. ${ month } ${ year }. à ${ hours }:${ minutes }`;
}
function gslTimeAgo(dateParam, id, eqId) {
    if (!dateParam) {
        return null;
    }

    const date = new Date(dateParam);
    const DAY_IN_MS = 86400000; // 24 * 60 * 60 * 1000
    const today = new Date();
    const yesterday = new Date(today - DAY_IN_MS);
    const seconds = Math.round((today - date) / 1000);
    const minutes = Math.round(seconds / 60);
    const isToday = today.toDateString() === date.toDateString();
    const isYesterday = yesterday.toDateString() === date.toDateString();
    const isThisYear = today.getFullYear() === date.getFullYear();
    var result;
    if (seconds < 60) {
        result = 'maintenant';
    }  else if (seconds < 90) {
        result = 'il y a une minute';
    } else if (minutes < 60) {
        result = `il y a ${ minutes } minutes`;
    } else if (isToday) {
        result = gslGetFormattedDate(date, 'Aujourd\'hui'); // Today at 10:20
    } else if (isYesterday) {
        result = gslGetFormattedDate(date, 'Hier'); // Yesterday at 10:20
    } else if (isThisYear) {
        result = gslGetFormattedDate(date, false, true); // 10. January at 10:20
    }else{
        result = gslGetFormattedDate(date); // 10. January 2017. at 10:20
    }

    if(minutes<10){
        $('.gsl-avatar-'+eqId).css('filter', 'grayscale(0)');
    }else if(minutes>=10 && minutes<20){
        $('.gsl-avatar-'+eqId).css('filter', 'grayscale(0.5)');
    }else{
        $('.gsl-avatar-'+eqId).css('filter', 'grayscale(1)');
    }
    cmd = $('.cmd.gsl-horodatage[data-cmd_id='+id+']');
    cmd.empty().append(result);
    if(gslObjects.intervals[id]){
        clearTimeout(gslObjects.intervals[id]);
    }
    gslObjects.intervals[id] = setTimeout(function(){gslTimeAgo(dateParam, id)}, 60000);
}

function gslLetterAvatar (name, size, color) {
    name  = name || '';
    size  = size || 60;

    var nameSplit = String(name).toUpperCase().split(' '),
        initials, canvas, context, dataURI;


    if (nameSplit.length == 1) {
        initials = nameSplit[0] ? nameSplit[0].charAt(0):'?';
    } else {
        initials = nameSplit[0].charAt(0) + nameSplit[1].charAt(0);
    }

    if (window.devicePixelRatio) {
        size = (size * window.devicePixelRatio);
    }
    
    canvas        = document.createElement('canvas');
    canvas.width  = size;
    canvas.height = size;
    context       = canvas.getContext("2d");
     
    context.fillStyle = color;
    context.fillRect (0, 0, canvas.width, canvas.height);
    context.font = Math.round(canvas.width/2)+"px Arial";
    context.textAlign = "center";
    context.fillStyle = "#FFF";
    context.fillText(initials, size / 2, size / 1.5);

    dataURI = canvas.toDataURL();
    canvas  = null;

    return dataURI;
}

function gslUpdateBattery(id, _options){
    var cmd = $('.cmd.gsl-battery[data-cmd_id='+id+']');
    cmd.empty().append(_options.display_value);
    var icon = 'fa-battery-empty';
    if(_options.display_value > 80){
        icon = 'fa-battery-full';
    }else if(_options.display_value > 60){
        icon = 'fa-battery-three-quarters';
    }else if(_options.display_value > 40){
        icon = 'fa-battery-half';
    }else if(_options.display_value > 20){
        icon = 'fa-battery-quarter';
    }
    cmd = $('.cmd.gsl-battery-icon[data-cmd_id='+id+']');
    cmd.find('i').attr('class', 'fa ' + icon);
}


function gslUpdateAccuracy(id, _options){
    var cmd = $('.cmd[data-cmd_id='+id+']');
    if(_options.display_value){
        cmd.empty().append('Précision : ' + _options.display_value + 'm');
    }else{
        cmd.empty();
    }
}

function gslUpdateAddress(id, _options, eqId){
    var cmd = $('.cmd[data-cmd_id='+id+']');
    cmd.empty().append(_options.display_value);
    cmd.attr('title','Date : '+_options.collectDate);

    gslTimeAgo(_options.collectDate, id, eqId);
}

function gslUpdateCharging(id, _options){
    var cmd = $('.cmd[data-cmd_id='+id+']');
    if(_options.display_value){
        cmd.find('i').addClass('fas fa-bolt');
    }else{
        cmd.find('i').removeClass('fas fa-bolt');
    }
}

function gslSetTheme(light, dark){
    if (!gslObjects.theme) {
        gslObjects.theme = light;
        if($('body')[0].hasAttribute('data-theme')){
            var currentTheme = $('body').attr('data-theme')
            if (currentTheme.endsWith('Dark')) {
                gslObjects.theme = dark;
            }
        }
        $('body').on('changeThemeEvent', function(event,data){
            if(data == 'Dark'){
                gslObjects.theme = dark;
            }else{
                gslObjects.theme = light;
            }
            for (const key in gslObjects.maps){
                var map = gslObjects.maps[key];
                map.map.removeLayer(map.layer);
                map.layer = new L.TileLayer(gslObjects.theme.url, gslObjects.theme);
                map.map.addLayer(map.layer);
            }
        });
    }
}

function gslCreateMap(eqId, attribution, zoom){
    var map = {markers:{}, circles:{}, histories:{}};
    map.layer = new L.TileLayer('/plugins/gsl/core/ajax/gsl.proxy.php?url='+gslObjects.theme.url, gslObjects.theme);
    map.featureGroup = L.featureGroup();
    map.map = L.map('map_' + eqId, {
        center: [51.5, -0.09],
        zoom: 15, 
        layers:[map.layer, map.featureGroup],
        attributionControl: attribution,
        zoomControl: zoom
    });
    gslObjects.maps[eqId] = map;
}

function gslCreateMarker(eqId, point, id){
    var avatar = (point.image && point.image.value ? ('/plugins/gsl/core/ajax/gsl.proxy.php?url='+point.image.value) : gslLetterAvatar(point.name.value, 36, point.color));
    $('.gsl-address img.gsl-avatar-'+id).attr('src', avatar);
  	if(!point.coordinated.value){
    	return;
    }
    var marker = L.marker(point.coordinated.value.split(','), {icon:  L.icon({
            iconUrl: avatar,
            shadowUrl: 'plugins/gsl/3rparty/images/avatar-pin-2x.png',
            iconSize: [36, 36],
            shadowSize: [50, 55],
            iconAnchor: [18, 47],
            shadowAnchor: [25, 55],
            popupAnchor: [-3, -76],
      		className: 'gsl-avatar-'+id
        }),
      		zIndexOffset: (point.type == 'fix' ?  -1000 : 1000)
         }).addTo(gslObjects.maps[eqId].featureGroup);
    marker._icon.style['background-color'] =  point.color;
    gslObjects.maps[eqId].markers[id] = marker;
    gslCreateCircle(eqId, point, id);
  	if(point.history){
    	gslCreateHistory(eqId, point, id);
    }
}

function gslCreateHistory(eqId, point, id){
        var history = L.polyline([], {
            color: point.color,
            fillColor: point.color,
            fillOpacity: 0.1,
            weight: 1.5
        }).addTo(gslObjects.maps[eqId].featureGroup);
        gslObjects.maps[eqId].histories[id] = {hours: point.history, feature: history};
}

function gslCreateCircle(eqId, point, id){
    if(point.accuracy && !isNaN(point.accuracy.value)){
        var circle = L.circle(point.coordinated.value.split(','), {
            radius: point.accuracy.value,
            color: point.color,
            fillColor: point.color,
            fillOpacity: 0.1,
              weight: 1
        }).addTo(gslObjects.maps[eqId].featureGroup);
        gslObjects.maps[eqId].circles[id] = circle;
    }
}

function gslUpdateMarker(eqId, coords, cmdId){
    for (const key in gslObjects.maps){
        var map = gslObjects.maps[key];
        if(map.markers[eqId]){
            map.markers[eqId].setLatLng(coords.split(','));
            if(map.circles[eqId]){
                map.circles[eqId].setLatLng(coords.split(','));
            }
          if(map.histories[eqId] && map.histories[eqId].feature && map.histories[eqId].hours){
          
              var dateStart = moment().subtract(map.histories[eqId].hours, 'hours').format('YYYY-MM-DD HH:mm:ss')
              var dateEnd = moment().format('YYYY-MM-DD HH:mm:ss')
              jeedom.history.get({
                  global: false,
                  cmd_id: cmdId,
                  dateStart: dateStart,
                  dateEnd: dateEnd,
                  context: {map: key, eqId: eqId},
                  success: function(result) {
                    if (result.data.length == 0) return false
                    var values = result.data.map(function(elt) {
                      return elt[1].split(',').map(function(coord) { return parseFloat(coord)}) });
                    gslObjects.maps[this.context.map].histories[result.eqLogic.id].feature.setLatLngs(values);
                  }
              });
            }
        }
        gslFocusFeatureGroup(key);
    }
}

function gslUpdateCircleRadius(id, radius){
    for (const key in gslObjects.maps){
        var map = gslObjects.maps[key];
        if(map.circles[id]){
            map.circles[id].setRadius(radius);
        }
        gslFocusFeatureGroup(key);
    }
}

function gslCreatePoint(eqId, point, id){
    if(point.battery){
        jeedom.cmd.update[point.battery.id] = function(_options) {
            gslUpdateBattery(point.battery.id, _options);
        }
        jeedom.cmd.update[point.battery.id]({display_value:point.battery.value});
    }

    if(point.accuracy){
        jeedom.cmd.update[point.accuracy.id] = function(_options) {
            gslUpdateAccuracy(point.accuracy.id, _options);
            gslUpdateCircleRadius(id, _options.display_value);
        }
        jeedom.cmd.update[point.accuracy.id]({display_value:point.accuracy.value});
    }

    if(point.address){
        jeedom.cmd.update[point.address.id] = function(_options) {
            gslUpdateAddress(point.address.id, _options, id);
        }
        jeedom.cmd.update[point.address.id]({display_value:point.address.value, collectDate:point.address.collectDate});
    }

    if(point.charging){
        jeedom.cmd.update[point.charging.id] = function(_options) {
            gslUpdateCharging(point.charging.id, _options);
        }
        jeedom.cmd.update[point.charging.id]({display_value:point.charging.value});
    }

    if(point.coordinated){
        jeedom.cmd.update[point.coordinated.id] = function(_options) {
            gslUpdateMarker(id, _options.display_value, point.coordinated.id);
        }
        jeedom.cmd.update[point.coordinated.id]({display_value:point.coordinated.value});
    }
}

function gslFocusFeatureGroup(eqId){
    if(!Object.keys(gslObjects.maps[eqId].markers).length){
  	    return;
    }
    gslObjects.maps[eqId].map.fitBounds(gslObjects.maps[eqId].featureGroup.getBounds(), {padding: [30, 30]});
    if(gslObjects.maps[eqId].customZoom){
        gslObjects.maps[eqId].map.setZoom(gslObjects.maps[eqId].customZoom);
    }
}

function gslMapLoaded(eqId){
    setTimeout(function(){
        gslObjects.maps[eqId].map.invalidateSize();
    },1);
}
