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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
$themes = gsl::getMapLayers();
?>
<form class="form-horizontal">
    <fieldset>
		<legend><i class="fa fa-list-alt"></i> {{Général}}</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Fréquence de rafraichissement}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="refresh::frequency" />
            </div>
        </div>
        <div class="form-group">
           <label class="col-lg-4 control-label">{{Forcer déconnexion}}</label>
           <div class="col-lg-2">
              <a class="btn btn-danger" id="bt_logoutGsl"><i class='fas fa-sign-out-alt icon-white'></i> {{Déconnexion}}</a>
          </div>
      </div>
  
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Cookie}}</label>
            <div class="col-lg-2">
  				<input type="file" name="file_cookieGsl" id="file_cookieGsl">
            </div>
  			<div class="col-lg-2">
                <a class="btn btn-success" id="bt_saveCookieGsl"><i class='far fa-check-circle icon-white'></i> {{Envoyer}}</a>
            </div>
        </div>
  </fieldset>
  <fieldset>
		<legend><i class="fa fa-map-marked-alt"></i> {{Carte}}</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Fond cartographique thème light}}</label>
            <div class="col-lg-4">
                <select class="configKey form-control" data-l1key="light-theme">
				<?php
				foreach ($themes as $key => $value) {
					echo '<option value="' . $key . '">' . $key . '</option>';
				}
				?>
				</select>
            </div>
        </div>
		<div class="form-group">
            <label class="col-lg-4 control-label">{{Fond cartographique thème dark}}</label>
            <div class="col-lg-4">
                <select class="configKey form-control" data-l1key="dark-theme">
				<?php
				foreach ($themes as $key => $value) {
					echo '<option value="' . $key . '">' . $key . '</option>';
				}
				?>
				</select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Afficher le contrôle du zoom}}</label>
            <div class="col-lg-4">
                <input type="checkbox" class="configKey form-control" data-l1key="control-zoom" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Afficher les attributions}}</label>
            <div class="col-lg-4">
                <input type="checkbox" class="configKey form-control" data-l1key="control-attributions" />
            </div>
        </div>
  </fieldset>
</form>
<script>
    $('#bt_saveCookieGsl').on('click', function () {
      var file = document.getElementById('file_cookieGsl').files[0];
        if (file) {
            // create reader
            var reader = new FileReader();
            reader.readAsText(file);
            reader.onload = function(e) {
               $.ajax({
                type: "POST",
                url: "plugins/gsl/core/ajax/gsl.ajax.php",
                data: {
                    action: "cookie",
                  cookie: e.target.result
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
                    $('#div_alert').showAlert({message: '{{Enregistement réussi}}', level: 'success'});
                }
            });
            };
        }
       
    });
    $('#bt_logoutGsl').on('click', function () {
        $.ajax({
            type: "POST",
            url: "plugins/gsl/core/ajax/gsl.ajax.php",
            data: {
                action: "logout",
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
                $('#div_alert').showAlert({message: '{{Déconnexion réussie}}', level: 'success'});
            }
        });
    });
</script>