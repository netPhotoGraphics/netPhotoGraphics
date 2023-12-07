<?php 
if (extensionEnabled('contact_form')) {
	include ('includes/header.php');
?>

	<div id="post">

		<div id="headline" class="clearfix">
			<h3><?php echo gettext('Contact'); ?></h3>
		</div>

		<div class="post">
			<?php printContactForm(); ?>
		</div>

	</div>

<?php
	include('includes/footer.php');

} else {
	include(CORE_SERVERPATH . '404.php');
} ?>