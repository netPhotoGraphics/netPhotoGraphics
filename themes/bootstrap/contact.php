<?php 
if (extensionEnabled('contact_form')) {
	include ('includes/header.php');
?>

	<!-- .container main -->
		<!-- .page-header -->
			<!-- .header -->
				<h3><?php echo gettext('Contact'); ?></h3>
			</div><!-- .header -->
		</div><!-- /.page-header -->

		<div class="row">
			<div class="col-sm-offset-1 col-sm-10">
				<div class="post">
					<?php printContactForm(); ?>
				</div>
			</div>
		</div>

	</div><!-- /.container main -->

<?php
	include('includes/footer.php');
} else {
	include(CORE_SERVERPATH . '404.php');
}
