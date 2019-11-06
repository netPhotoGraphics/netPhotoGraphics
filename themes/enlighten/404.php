<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html>
<head>
	<?php
	npgFilters::apply('theme_head');

	scriptLoader($_themeroot . '/style.css');
	?>
</head>

<body>
	<?php npgFilters::apply('theme_body_open'); ?>

	<div id="main">

		<div id="header">
			<h1><?php echo getGalleryTitle(); ?></h1>
		</div>

		<div id="content">
			<div id="breadcrumb">
				<h2><a href="<?php echo getGalleryIndexURL(); ?>">Index</a> » <strong><?php echo gettext("Object not found"); ?></strong></h2>
			</div>

			<div id="content-error">

				<div class="errorbox">
					<?php print404status(); ?>
				</div>

			</div>



			<div id="footer">
				<?php include("footer.php"); ?>
			</div>



		</div><!-- content -->

	</div><!-- main -->
	<?php npgFilters::apply('theme_body_close'); ?>
</body>
</html>
