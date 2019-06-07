<?php
if (!defined('WEBPATH'))
	die();
if (class_exists('CMS')) {
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<?php
			npgFilters::apply('theme_head');

			scriptLoader($_themeroot . '/zen.css');

			if (class_exists('RSS'))
				printRSSHeaderLink('Gallery', gettext('Gallery'));
			?>
		</head>
		<body class="sidebars">
	<?php npgFilters::apply('theme_body_open'); ?>
			<div id="navigation"></div>
			<div id="wrapper">
				<div id="container">
					<div id="header">
						<div id="logo-floater">
							<div>
								<h1 class="title"><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo html_encode(getGalleryTitle()); ?></a></h1>
								<span id="galleryDescription"><?php printGalleryDesc(); ?></span>
							</div>
						</div>
					</div>
					<!-- header -->
					<div class="sidebar">
						<div id="leftsidebar">
	<?php include("sidebar.php"); ?>
						</div>
					</div>

					<div id="center">
						<div id="squeeze">
							<div class="right-corner">
								<div class="left-corner">
									<!-- begin content -->
									<div class="main section" id="main">
										<h2 id="gallerytitle">
											<?php printHomeLink('', ' » '); ?>
											<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo html_encode(getGalleryTitle()); ?></a>
	<?php printZenpageItemsBreadcrumb(" » ", ""); ?><?php printPageTitle(" » "); ?>
										</h2>
										<h3><?php printPageTitle(); ?></h3>
										<div id="pagetext">
											<?php printCodeblock(1); ?>
											<?php printPageContent(); ?>
										<?php printCodeblock(2); ?>
										</div>
										<?php
										@call_user_func('printCommentForm');
										footer();
										?>
										<p style="clear: both;"></p>
									</div>
									<!-- end content -->
									<span class="clear"></span> </div>
							</div>
						</div>
					</div>
					<span class="clear"></span>
					<div class="sidebar">
						<div id="rightsidebar">
	<?php printTags('links', gettext('Tags: '), NULL, ''); ?>
						</div><!-- right sidebar -->
					</div><!-- sidebar -->
				</div><!-- /container -->
			</div>
			<?php
			npgFilters::apply('theme_body_close');
			?>
		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>