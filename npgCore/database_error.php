<?php

/**
 * Displays database failure message
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
function displayQueryError($sql) {
	if (defined('TESTING_MODE') && TESTING_MODE) {
		trigger_error(sprintf(gettext('%1$s Error: ( %2$s ) failed. %1$s returned the error %3$s'), DATABASE_SOFTWARE, $sql, db_errorno() . ': ' . db_error()), E_USER_ERROR);
	} else {

		$reason = sprintf(gettext('%1$s Error %2$s'), DATABASE_SOFTWARE, db_errorno() . ': ' . db_error());

		debugLogBacktrace(sprintf(gettext("Database Server error:\n %1\$s\n returned\n %2\$s."), $sql, $reason));
	}
	?>
	<style type="text/css">
		.reasonbox {
			text-align: left;
			padding-top: 10px;
			padding-bottom: 10px;
			padding-left: 10px;
			padding-right: 10px;
			color: black;
			background-color: #FFEFB7;
			border-width: 1px 1px 2px 1px;
			border-color: #FFDEB5;
			border-style: solid;
			margin-bottom: 10px;
			font-size: 100%;
			box-sizing: content-box !important;
			webkit-box-sizing: content-box !important;
		}

		#error_content {
			text-align: left;
			padding-left: 1em;
			padding-right: 1em;
			padding-top: 10px;
			padding-bottom: 5px;
			background-color: rgba(255,255,244,0.85);
			border: 3px solid #CBCBCB;
			position:fixed;
			top: 50px;
			left: 100px;
			min-width: 640px;
			z-index: 89999;
		}

		#error_content h1 {
			color: orangered;
			font-size: 2em;
			font-weight: bold;
			margin-bottom: 1em;
		}
	</style>
	<br />
	<div id="error_content">
		<h1><?php echo gettext('Database Server Error'); ?></h1>
		<div class="reasonbox">
	<?php echo $reason; ?>
		</div>
	</div>
	<?php
}
