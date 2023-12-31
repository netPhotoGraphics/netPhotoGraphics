<?php 
if (class_exists('CMS')) {
	include ('includes/header.php');
?>

		<div id="post" class="clearfix">
			<h3><?php printPageTitle(); ?></h3>
			<?php if (getPageExtraContent()) { ?>
			<div class="extra-content">
				<?php printPageExtraContent(); ?>
			</div>
			<?php } ?>

			<?php printPageContent(); ?>
			<?php printCodeblock(1); ?>
		</div>

		<?php if (extensionEnabled('comment_form')) { ?>
			<?php include('includes/print_comment.php'); ?>
		<?php } ?>

<?php
	include('includes/footer.php');

} else {
	include(CORE_SERVERPATH . '404.php');
} ?>