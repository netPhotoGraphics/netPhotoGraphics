<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/head.php'); ?>
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/header.php'); ?>

<div id="background-main" class="background">
	<div class="container<?php if (getOption('full_width')) {echo '-fluid';}?>">
	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/breadcrumbs.php'); ?>
		<div id="center" class="row" itemscope itemtype="http://schema.org/ImageGallery">
			
			<section class="col-sm-9" id="main" itemprop="mainContentOfPage">
				
			<h1 itemprop="name"><?php echo gettext("Albums"); ?></h1>
				
				<?php include("includes/albumlist.php"); ?>

			</section>
<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/sidebar.php'); ?>
		</div>	
	</div>	
</div>		

<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/footer.php'); ?>