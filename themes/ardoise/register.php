<?php
if (extensionEnabled('register_user')) {
	include ('includes/header.php');
?>

	<div id="post">

		<div id="headline" class="clearfix">
			<h3><?php echo gettext('User Registration') ?></h3>
		</div>

		<div class="post">
			<div id="registration"><?php printRegistrationForm(); ?></div>
		</div>
	</div>

<?php
	include('includes/footer.php');

} else {
	include(CORE_SERVERPATH . '404.php');
} ?>