<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/_head.php'); ?>
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/_header.php'); ?>

<div id="background-main" class="background">
	<div class="container<?php
	if (getOption('full_width')) {
		echo '-fluid';
	}
	?>">
				 <?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/_breadcrumbs.php'); ?>
		<div id="center" class="row" itemscope itemtype="http://schema.org/WebPage">
			<section class="col-sm-12" id="main" itemprop="mainContentOfPage">

				<?php
				include getPlugin('/daily-summary/daily-summary_content.php');
				?>

			</section>
		</div>
	</div>
</div>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/_footer.php'); ?>