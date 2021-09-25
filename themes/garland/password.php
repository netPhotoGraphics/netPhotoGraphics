<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<?php
		npgFilters::apply('theme_head');

		scriptLoader($_themeroot . '/garland.css');

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
							<h1 class="title"><a href="<?php echo getGalleryIndexURL(); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php printGalleryTitle(); ?></a></h1>
						</div>
					</div>
				</div>
				<!-- header -->
				<div class="sidebar">
					<div id="leftsidebar">
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
										<a href="<?php echo getGalleryIndexURL(); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php printGalleryTitle(); ?></a>	<?php
										if (isset($hint)) {
											?>
											»	<?php
											echo "<em>" . gettext('Password required') . "</em>";
										}
										?>
									</h2>
									<?php if (isset($hint)) {
										?>
										<h3><?php echo gettext('A password is required to access this page.') ?></h3>
										<?php
									}
									printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false);
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
			</div><!-- /container -->
		</div>
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>
