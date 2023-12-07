<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
if (function_exists('printRegistrationForm')) {	
?>

<!DOCTYPE html>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/head.php'); ?>
<meta name="robots" content="noindex, nofollow">
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/header.php'); ?>

<div id="background-main" class="background">
	<div class="container<?php if (getOption('full_width')) {echo '-fluid';}?>">
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/breadcrumbs.php'); ?>

		<div id="center" class="row">

			<section class="col-sm-9" id="main">
				
			<h1><?php echo gettext('User Registration') ?></h1>
			
			<?php printRegistrationForm(); ?>
			
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