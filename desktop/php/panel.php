<?php
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJs('jeedomBackgroundImg', 'plugins/gsl/core/img/panel.jpg');
?>
  <style>
   .gsl-card-global {
    	grid-column: 1 / span 4;
    }
      
  .gsl-cards {
    max-width: 100%;
    margin: 0 auto;
    display: grid;
    grid-gap: 4px;
    grid-template-columns: repeat(4, 1fr);
    height: 90vh;
  }
  </style>
    <div class="gsl-cards" id="div_displayObject">
		<?php
$count = 0;
$eqLogic = eqLogic::byLogicalId('global', 'gsl');
if ($eqLogic->getConfiguration('isVisiblePanel', 0)) {
	echo '<div class="gsl-card-global">'.$eqLogic->toHtml('dview').'</div>';
	$count++;
}
$gsls = gsl::byType('gsl', true);
foreach ($gsls as $gsl) {
	if ($gsl->getLogicalId() == 'global') {
		continue;
	}
	if ($gsl->getConfiguration('isVisiblePanel', 0)) {
		
	echo '<div class="gsl-card-widget">'.$gsl->toHtml('dview').'</div>';
		$count++;
	}
}
?>
    </div>
    </div>