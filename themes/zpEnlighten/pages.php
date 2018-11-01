<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<head>
	<?php
	zp_apply_filter('theme_head');

	scriptLoader($_zp_themeroot . '/style.css', false);

	printRSSHeaderLink("Gallery", gettext('Gallery'));
	printZDRoundedCornerJS();
	?>
</head>

<body>
	<?php zp_apply_filter('theme_body_open'); ?>

	<div id="main">

		<?php include("header.php"); ?>

		<div id="content">

			<div id="breadcrumb">
				<h2><a href="<?php echo getGalleryIndexURL(); ?>"><?php echo gettext("Index"); ?></a><?php
					if (!isset($ishomepage)) {
						printZenpageItemsBreadcrumb(" » ", "");
					}
					?><strong><?php
						if (!isset($ishomepage)) {
							printPageTitle(" » ");
						}
						?></strong>
				</h2>
			</div>
			<div id="content-left">

				<?php
				printPageContent();
				printCodeblock(1);
				printTags('links', gettext('<strong>Tags:</strong>') . ' ', 'taglist', ', ');
				?>
				<br style="clear:both;" /><br />

				<?php if (function_exists('printCommentForm')) { ?>
					<div id="comments">
						<?php printCommentForm(); ?>
					</div>
				<?php } ?>
			</div><!-- content left-->


			<div id="sidebar">
				<?php include("sidebar.php"); ?>
			</div><!-- sidebar -->


			<div id="footer">
				<?php include("footer.php"); ?>
			</div>

		</div><!-- content -->

	</div><!-- main -->
	<?php zp_apply_filter('theme_body_close'); ?>
</body>
</html>