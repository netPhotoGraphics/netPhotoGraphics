<?php include ('inc_header.php'); ?>

<div id="post">

	<div id="headline" class="clearfix">
		<h3><?php echo gettext('Daily summary'); ?></h3>
	</div>

	<div class="post">
		<?php
		include getPlugin('/daily-summary/daily-summary_content.php');
		?>
	</div>

</div>

<?php include('inc_footer.php'); ?>