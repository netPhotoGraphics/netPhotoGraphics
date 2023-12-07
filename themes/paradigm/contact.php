<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
if (function_exists('printContactForm')) {
	?>
<!DOCTYPE html>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/head.php'); ?>
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/header.php'); ?>

<div id="background-main" class="background">
	<div class="container<?php if (getOption('full_width')) {echo '-fluid';}?>">
	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/breadcrumbs.php'); ?>
		<div id="center" class="row" itemscope itemtype="http://schema.org/ContactPage">
			<section class="col-sm-9" id="main" itemprop="mainContentOfPage">

			<h1><?php echo gettext('Contact us') ?></h1>
				
			<p>						
				<?php
						printContactForm();
				?>
			</p>
				
			</section>
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/sidebar.php'); ?>
		</div>
	</div>
</div>		

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/footer.php'); ?>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
	?>