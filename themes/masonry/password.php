<?php include ("inc-header.php"); ?>

<div id="breadcrumbs">
	<a href="<?php echo $zpmas_homelink; ?>" title="<?php echo gettext("Gallery Index"); ?>"><?php echo gettext("Gallery Index"); ?></a> &raquo; <?php echo gettext('Please Login'); ?>
</div>
<div id="wrapper">
	<div id="sidebar">
		<div id="sidebar-inner">
			<div id="sidebar-padding">
				<?php include ("inc-copy.php"); ?>
			</div>
		</div>
	</div>
	<div id="page">
		<div class="post">
			<?php if (!npg_loggedin()) { ?>
				<div class="error"><?php echo gettext("Please Login"); ?></div>
				<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false); ?>
			<?php } else { ?>
				<div class="errorbox">
					<p><?php echo gettext('You are logged in...'); ?></p>
				</div>
			<?php } ?>

			<?php
			if (!npg_loggedin() && function_exists('printRegistrationForm') && $_gallery->isUnprotectedPage('register')) {
				printCustomPageURL(gettext('Register for this site'), 'register', '', '<br />');
				echo '<br />';
			}
			?>
		</div>
	</div>
</div>

<?php include ("inc-footer.php"); ?>