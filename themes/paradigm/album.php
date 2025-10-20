<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/head.php'); ?>
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/header.php'); ?>

<div id="background-main" class="background">
	<div class="container<?php
	if (getOption('full_width')) {
		echo '-fluid';
	}
	?>">
				 <?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/breadcrumbs.php'); ?>
		<div id="center" class="row" itemscope itemtype="https://schema.org/ImageGallery">
			<section class="col-sm-9" id="main" itemprop="mainContentOfPage">

				<h1 itemprop="name"><?php printAlbumTitle(); ?></h1>

				<div itemprop="description" class="content"><?php printAlbumDesc(); ?></div>

				<?php include("includes/albumlist.php"); ?>

				<?php include("includes/imagethumbs.php"); ?>

				<?php printPageListWithNav("« " . gettext("prev"), gettext("next") . " »"); ?>

				<!-- Tags -->
				<?php
				if (getTags()) {
					echo '<h2>' . gettext('Tags') . '</h2>';
					printTags_zb('links', '', 'taglist', ', ');
				}
				?>

				<?php if (function_exists('printAddToFavorites')) printAddToFavorites($_current_album); ?>

				<!-- Rating -->
				<?php
				if (extensionEnabled('rating')) {
					echo '<div id="rating">';
					echo '<h2>' . gettext('Rating') . '</h2>';
					printRating();
					echo '</div>';
				}
				?>

				<!-- Codeblock 1 -->
				<?php
				printcodeblock(1, $_current_album);
				?>
				<?php
				printcodeblock(2, $_current_album);
				?>

				<?php
				simpleMap::setMapDisplay('colorbox');
				simpleMap::printMap();
				?>

				<br style="clear:both;" />

				<?php if (function_exists('printCommentForm')) printCommentForm(); ?>

			</section>

			<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/sidebar.php'); ?>
		</div>
	</div>
</div>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/footer.php'); ?>
