<?php
if (extensionEnabled('register_user')) {
	include ('includes/header.php');
?>

	<!-- .container -->
		<!-- .page-header -->
			<!-- .header -->
				<h3><?php echo gettext('User Registration') ?></h3>
			</div><!-- .header -->
		</div><!-- /.page-header -->

		<div class="row">
			<div class="col-sm-offset-1 col-sm-10">
				<div class="post">
					<?php printRegistrationForm(); ?>
				</div>
			</div>
		</div>

	</div><!-- /.container main -->

<?php
	include('includes/footer.php');
} else {
	include(SERVERPATH . '/' . CORE_FOLDER . '/404.php');
}
?>