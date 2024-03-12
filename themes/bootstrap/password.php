<?php include ('includes/header.php'); ?>

	<!-- .container main -->
		<!-- .page-header -->
			<!-- .header -->
				<h3><?php echo gettext('Password required'); ?></h3>
			</div><!-- .header -->
		</div><!-- /.page-header -->

		<div id="password" class="modal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-body">
						<?php printPasswordForm('', true); ?>
						<script>
						//<![CDATA[
							$(document).ready( function($) {
								$('#password').modal('show');
							});
						//]]>
						</script>
					</div>
				</div>
			</div>
		</div>

	</div><!-- /.container main -->

<?php include('includes/footer.php');
