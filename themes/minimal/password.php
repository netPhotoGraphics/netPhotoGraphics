<?php include ("inc-header.php"); ?>

<div id="breadcrumbs">
	<h2><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Home'); ?>"><?php echo gettext('Home'); ?></a> &raquo; <a href="<?php echo getCustomPageURL('gallery'); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo gettext('Gallery Index'); ?></a> &raquo;  <?php echo gettext('Enter Login'); ?></h2>
</div>
</div> <!-- close #header -->
<div id="content">
	<div id="main"<?php if ($zpmin_switch) echo ' class="switch"'; ?>>
		<div id="random-image">
			<?php printRandomImages(1, null, 'all', '', 190, 225, true); ?>
		</div>
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
	<div id="sidebar"<?php if ($zpmin_switch) echo ' class="switch"'; ?>>
		<div class="sidebar-divide">
		<?php printGalleryDesc(true); ?>
		</div>
<?php include ("inc-sidemenu.php"); ?>
	</div>
</div>

<?php include ("inc-footer.php"); ?>