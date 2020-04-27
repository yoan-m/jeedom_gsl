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
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    //tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

function printEqLogic(_eqLogic) {
    if (!isset(_eqLogic)) {
        var _eqLogic = {configuration: {}};
    }
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }
    if (_eqLogic.logicalId == 'global') {
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=cmdgeoloc]').closest('.form-group').hide();
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=isVisibleGlobal]').closest('.form-group').hide();
   	  $('.eqLogicAttr[data-l1key=configuration][data-l2key=color]').closest('.form-group').hide();
   	  $('.eqLogicAction[data-action=remove]').hide();
       
  } else {
     $('.eqLogicAttr[data-l1key=configuration][data-l2key=cmdgeoloc]').closest('.form-group').show();
     $('.eqLogicAttr[data-l1key=configuration][data-l2key=isVisibleGlobal]').closest('.form-group').show();
     $('.eqLogicAttr[data-l1key=configuration][data-l2key=color]').closest('.form-group').show();
   	  $('.eqLogicAction[data-action=remove]').show();
 }
 if(isset(_eqLogic.configuration.type) && _eqLogic.configuration.type == 'fix'){
  $('.eqLogicAttr[data-l1key=configuration][data-l2key=cmdgeoloc]').closest('.form-group').hide();
  $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').show();
  $('.eqLogicAttr[data-l1key=configuration][data-l2key=isVisibleGlobal]').closest('.form-group').show();
}else{
   $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').hide();
   $('.eqLogicAttr[data-l1key=configuration][data-l2key=isVisiblePanel]').closest('.form-group').show();
}
}

$('.eqLogicAttr[data-l1key=configuration][data-l2key=cmdgeoloc]').next().on('click', function () {
    jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
        $('.eqLogicAttr[data-l2key=cmdgeoloc]').value(result.human);
    });
});
