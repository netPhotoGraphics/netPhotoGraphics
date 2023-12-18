<?php
/**
 * Displays database failure message
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
if (defined('TESTING_MODE') && TESTING_MODE) {
	trigger_error(sprintf(gettext('%1$s Error: ( %2$s ) failed. %1$s returned the error %3$s'), DATABASE_SOFTWARE, $sql, db_errorno() . ': ' . db_error()), E_USER_ERROR);
} else {

	$reason = sprintf(gettext('%1$s Error %2$s'), DATABASE_SOFTWARE, db_errorno() . ': ' . db_error());

	debugLogBacktrace(sprintf(gettext('Setup required: %1$s.'), $reason));
	?>
	<style type="text/css">
		.reasonbox {
			text-align: left;
			padding: 10px;
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

		.reasonbox h1,.notebox strong {
			color: #663300;
			font-size: 120%;
			font-weight: bold;
			margin-bottom: 1em;
		}

		#error_content {
			text-align: left;
			padding-left: 14em;
			padding-right: 1em;
			background-color: #f1f1f1;
			border-top: 1px solid #CBCBCB;
		}

	</style>
	<div id="error_content">
		<br clear="all">
		<h1><?php echo gettext('Database Server Error'); ?></h1>
		<div>
			<div class="reasonbox">
				<?php echo $reason; ?>
			</div>
		</div>
	</div>
	<?php
	db_close();
	exit();
}