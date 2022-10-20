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
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 100%;" placeholder="{{Nom}}">';
    tr += '</td>';
	tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
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
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=precision]').closest('.form-group').hide();
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=precisionFiltre]').closest('.form-group').hide();
   	  $('.eqLogicAction[data-action=remove]').hide();
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=history]').closest('.form-group').hide();
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=historyGlobal]').closest('.form-group').hide();
  } else {
     $('.eqLogicAttr[data-l1key=configuration][data-l2key=cmdgeoloc]').closest('.form-group').show();
     $('.eqLogicAttr[data-l1key=configuration][data-l2key=isVisibleGlobal]').closest('.form-group').show();
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=history]').closest('.form-group').show();
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=historyGlobal]').closest('.form-group').show();
     checkPrecisionFilter();
   	 $('.eqLogicAction[data-action=remove]').show();
 }
 if(isset(_eqLogic.configuration.type) && _eqLogic.configuration.type == 'fix'){
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=cmdgeoloc]').closest('.form-group').hide();
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=isVisibleGlobal]').closest('.form-group').show();
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinatesType]').closest('.form-group').show();
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=precision]').closest('.form-group').hide();
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=precisionFiltre]').closest('.form-group').hide();
  }else{
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').hide();
    $('.form-control[data-l1key=configuration][data-l2key=coordinatesJeedom]').closest('.form-group').hide();
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=isVisiblePanel]').closest('.form-group').show();
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinatesType]').closest('.form-group').hide();
  }
}

$('.eqLogicAttr[data-l1key=configuration][data-l2key=cmdgeoloc]').next().on('click', function () {
    jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
        $('.eqLogicAttr[data-l2key=cmdgeoloc]').value(result.human);
    });
});
$(".cmdSendSel").on('click', function () {
    var el = $(this);
  jeedom.cmd.getSelectModal({cmd:{type:'info'}}, function(result) {
       var calcul = el.closest('div').find('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]');
       calcul.val('');
       calcul.atCaret('insert', result.human);
     });
});


$('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinatesType]').on('change', function (event) {
    if($('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinatesType]').val() == 'jeedom'){
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').hide();
    }else if($('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinatesType]').val() == 'cmd'){
      	 $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').show();
      	$('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').parent().addClass('input-group');
      	 $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').find('.input-group-btn').show();
      
    }else{
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').show();
      	$('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').parent().removeClass('input-group');
       $('.eqLogicAttr[data-l1key=configuration][data-l2key=coordinated]').closest('.form-group').find('.input-group-btn').hide();
    }
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=precisionFiltre]').on('change', checkPrecisionFilter);

function checkPrecisionFilter(){
   if($('.eqLogicAttr[data-l1key=configuration][data-l2key=precisionFiltre]').is(':checked') ){
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=precision]').closest('.form-group').show();
    }else{
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=precision]').closest('.form-group').hide();
    }
}
