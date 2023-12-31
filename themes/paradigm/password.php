<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
?>

<!DOCTYPE html>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/head.php'); ?>
<meta name="robots" content="noindex, nofollow">
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/header.php'); ?>

<div id="background-main" class="background">
	<div class="container<?php if (getOption('full_width')) {
	echo '-fluid';
} ?>">
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/breadcrumbs.php'); ?>
		<div id="center" class="row">

			<section class="col-sm-9" id="main">
				<?php if (isset($hint)) {
					?>
					<strong><?php echo gettext("A password is required for the page you requested"); ?></strong>
					<?php
				}
				?>
				<p><?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false); ?></p>

				<?php
				if (!npg_loggedin() && function_exists('printRegisterURL') && $_gallery->isUnprotectedPage('register')) {
					printRegisterURL(gettext('Register for this site'), '<br />');
					echo '<br />';
				}
				?>

			</section>
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/sidebar.php'); ?>
		</div>
	</div>
</div>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/footer.php'); ?>