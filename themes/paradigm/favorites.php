<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
if (class_exists('favorites')) {
	?>
	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/head.php'); ?>
	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/header.php'); ?>
	<div id="background-main" class="background">
		<div class="container<?php if (getOption('full_width')) {
		echo '-fluid';
	} ?>">
	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/breadcrumbs.php'); ?>

			<div id="center" class="row" itemscope itemtype="http://schema.org/ImageGallery">

				<section class="col-sm-9" id="main" itemprop="mainContentOfPage">


					<h1><?php printAlbumTitle(); ?></h1>
					<p class="lead"><?php printAlbumDesc(); ?></p>

					<?php include("includes/albumlist.php"); ?>

					<?php include("includes/imagethumbs.php"); ?>
					<?php printPageListWithNav("« " . gettext("prev"), gettext("next") . " »"); ?>

	<?php if (function_exists('printRating')) printRating(); ?>
	<?php if (function_exists('printCommentForm')) printCommentForm(); ?>

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