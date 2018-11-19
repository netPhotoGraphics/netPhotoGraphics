<?php include ("inc-header.php"); ?>
<div class="wrapper contrast top">
	<div class="container">
		<div class="sixteen columns">
			<?php include ("inc-search.php"); ?>
			<h1><?php echo gettext('Daily summary') ?></h1>
		</div>
	</div>
</div>
<div class="wrapper">
	<div class="container">
		<?php
		include getPlugin('/daily-summary/daily-summary_content.php');
		?>
	</div>
</div>
<?php include ("inc-bottom.php"); ?>
<?php include ("inc-footer.php"); ?>