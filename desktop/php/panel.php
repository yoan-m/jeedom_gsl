<?php
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJs('jeedomBackgroundImg', 'plugins/gsl/core/img/panel.jpg');
?>
    <div class="col-lg-12 col-md-12 col-sm-12" id="div_displayObject">
		<?php
echo '<div class="div_displayEquipement" style="width: 100%;">';
$count = 0;
$eqLogic = eqLogic::byLogicalId('global', 'gsl');
if ($eqLogic->getConfiguration('isVisiblePanel', 0)) {
	echo $eqLogic->toHtml('dview');
	$count++;
}
$gsls = gsl::byType('gsl', true);
foreach ($gsls as $gsl) {
	if ($gsl->getLogicalId() == 'global') {
		continue;
	}
	if ($gsl->getConfiguration('isVisiblePanel', 0) == 0) {
		continue;
	}
	echo $gsl->toHtml('dview');
	$count++;
}
echo '</div>';
sendVarToJs('nbGslWidget', $count);
?>
    </div>
    </div>
<?php include_file('desktop', 'panel', 'js', 'gsl');?>