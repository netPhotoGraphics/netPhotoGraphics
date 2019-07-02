<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="<?php echo getOption('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
		<?php
		if (!((($_gallery_page == 'pages.php') && (getPageTitleLink() == 'map')) || ($_gallery_page == 'album.php'))) {
			npgFilters::remove('theme_head', 'GoogleMap::js');
			npgFilters::remove('theme_head', 'openStreetMap::scripts');
		}
		npgFilters::apply('theme_head');
		?>
		<title>
			<?php
			echo getMainSiteName() . ' | ';
			switch ($_gallery_page) {
				case 'index.php':
					if (isset($isHomePage)) {
						echo gettext('Home');
					} else {
						echo gettext('Gallery');
					};
					break;
				case '404.php':
					echo gettext('Object not found');
					break;
				case 'album.php':
					echo getBareAlbumTitle();
					if ($_current_page > 1) {
						echo ' [' . $_current_page . ']';
					};
					break;
				case 'archive.php':
					echo gettext('Archive View');
					break;
				case 'summary.php':
					echo gettext('Daily summary');
					break;
				case 'contact.php':
					echo gettext('Contact');
					break;
				case 'favorites.php':
					echo gettext('My favorites');
					if ($_current_page > 1) {
						echo ' [' . $_current_page . ']';
					};
					break;
				case 'gallery.php':
					echo gettext('Gallery');
					if ($_current_page > 1) {
						echo ' [' . $_current_page . ']';
					};
					break;
				case 'image.php':
					echo getBareAlbumTitle() . ' | ' . getBareImageTitle();
					break;
				case 'news.php':
					if (is_NewsArticle()) {
						echo getBareNewsTitle();
					} else {
						echo NEWS_LABEL;
						if ($_current_page > 1) {
							echo ' [' . $_current_page . ']';
						};
					};
					break;
				case 'pages.php':
					echo getBarePageTitle();
					break;
				case 'password.php':
					echo gettext('Password Required...');
					break;
				case 'register.php':
					echo gettext('Register');
					break;
				case 'search.php':
					echo gettext('Search');
					if ($_current_page > 1) {
						echo ' [' . $_current_page . ']';
					};
					break;
			}
			?>
		</title>

		<?php
		if (extensionEnabled('rss')) {
			if (($_zenpage_news_enabled) && (getOption('RSS_articles'))) {
				printRSSHeaderLink('News', NEWS_LABEL);
			} else if (getOption('RSS_album_image')) {
				printRSSHeaderLink('Gallery', gettext('Latest images RSS'));
			}
		}
		?>
		<link rel="shortcut icon" href="<?php echo $_themeroot; ?>/images/favicon.ico" />
		<?php
		scriptLoader($_themeroot . '/css/bootstrap.min.css');
		if (($_gallery_page == 'index.php') && (isset($isHomePage))) {
			scriptLoader($_themeroot . '/css/flexslider.css');
		}
		if (($_gallery_page == 'album.php') || ($_gallery_page == 'favorites.php') || ($_gallery_page == 'news.php') || ($_gallery_page == 'pages.php') || ($_gallery_page == 'search.php')) {
			scriptLoader($_themeroot . '/css/jquery.fancybox.min.css');
		}
		scriptLoader($_themeroot . '/css/zpBootstrap.css');
		?>
		<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
		<?php
		scriptLoader($_themeroot . '/js/bootstrap.min.js');
		scriptLoader($_themeroot . '/js/zpBootstrap.js');

		if (($_gallery_page == 'index.php') && isset($isHomePage)) {
			scriptLoader($_themeroot . '/js/jquery.flexslider-min.js');
			?>
			<script type="text/javascript">
				//<![CDATA[
				$(document).ready(function() {
				$('.flexslider').flexslider({
				slideshowSpeed: 5000,
								animationDuration: 500,
								randomize: true,
								pauseOnAction: false,
								pauseOnHover: true
				});
				});
				//]]>
			</script>
		<?php } ?>

		<?php
		if (($_gallery_page == 'album.php') || ($_gallery_page == 'favorites.php') || ($_gallery_page == 'news.php') || ($_gallery_page == 'pages.php') || ($_gallery_page == 'search.php')) {
			scriptLoader($_themeroot . '/js/jquery.fancybox.min.js');
			scriptLoader($_themeroot . '/js/zpB_fancybox_config.js');
			?>
			<script type="text/javascript">
				//<![CDATA[
				$(document).ready(function() {
				$.fancybox.defaults.lang = '<?php
		$loc = substr(getOption('locale'), 0, 2);
		if (empty($loc)) {
			$loc = 'en';
		};
		echo $loc;
		?>';
				$.fancybox.defaults.i18n = {
				'<?php echo $loc; ?>' : {
				CLOSE		: '<?php echo gettext('close'); ?>',
								NEXT		: '<?php echo gettext('next'); ?>',
								PREV		: '<?php echo gettext('prev'); ?>',
								PLAY_START	: '<?php echo gettext('start slideshow'); ?>',
								PLAY_STOP	: '<?php echo gettext('stop slideshow'); ?>',
								THUMBS		: '<?php echo gettext('thumbnails'); ?>'
				}
				};
				// cohabitation between keyboard Navigation and Fancybox
				$.fancybox.defaults.onInit = function() { FancyboxActive = true; };
				$.fancybox.defaults.afterClose = function() { FancyboxActive = false; };
				});
				//]]>
			</script>
		<?php } ?>

		<?php if (($_gallery_page == 'image.php') || ($_zenpage_news_enabled && is_NewsArticle())) { ?>
			<script type="text/javascript">
				//<![CDATA[
	<?php
	$NextURL = $PrevURL = false;
	if ($_gallery_page == 'image.php') {
		if (hasNextImage()) {
			?>var nextURL = "<?php
			echo html_encode(getNextImageURL());
			$NextURL = true;
			?>";<?php
		}
		if (hasPrevImage()) {
			?>var prevURL = "<?php
			echo html_encode(getPrevImageURL());
			$PrevURL = true;
			?>";<?php
		}
	} else {
		if ($_zenpage_news_enabled && is_NewsArticle()) {
			if (getNextNewsURL()) {
				$article_url = getNextNewsURL();
				?>var nextURL = "<?php
				echo html_decode($article_url['link']);
				$NextURL = true;
				?>";<?php
			}
			if (getPrevNewsURL()) {
				$article_url = getPrevNewsURL();
				?>var prevURL = "<?php
				echo html_decode($article_url['link']);
				$PrevURL = true;
				?>";<?php
			}
		}
	}
	?>

				// cohabitation between keyboard Navigation and Fancybox
				var FancyboxActive = false;
				function keyboardNavigation(e) {
				// keyboard Navigation disabled if Fancybox active
				if (FancyboxActive) return true;
				if (!e) e = window.event;
				if (e.altKey) return true;
				var target = e.target || e.srcElement;
				if (target && target.type) return true; //an input editable element
				var keyCode = e.keyCode || e.which;
				var docElem = document.documentElement;
				switch (keyCode) {
				case 63235: case 39:
								if (e.ctrlKey || (docElem.scrollLeft == docElem.scrollWidth - docElem.clientWidth)) {
	<?php if ($NextURL) { ?>window.location.href = nextURL; <?php } ?>return false; }
		break;
		case 63234: case 37:
						if (e.ctrlKey || (docElem.scrollLeft == 0)) {
	<?php if ($PrevURL) { ?>window.location.href = prevURL; <?php } ?>return false; }
		break;
		}
		return true;
		}

		document.onkeydown = keyboardNavigation;
		//]]>
			</script>
		<?php } ?>

	</head>

	<body>
		<?php
		npgFilters::apply('theme_body_open');

		if (((!getOption('zpB_homepage')) && ($_gallery_page == 'index.php')) ||
						($_gallery_page == 'gallery.php') ||
						($_gallery_page == 'album.php') ||
						($_gallery_page == 'image.php')) {
			$galleryactive = true;
		} else {
			$galleryactive = false;
		}
		?>

		<nav id="menu" class="navbar navbar-inverse navbar-static-top">
			<div class="container"> <!-- class="navbar-inner" -->
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Home'); ?>"><?php echo getMainSiteName(); ?></a>
				</div>
				<div id="navbar" class="collapse navbar-collapse">
					<ul class="nav navbar-nav pull-right">
						<?php if ((extensionEnabled('menu_manager')) && (getThemeOption('zpB_custom_menu'))) { ?>
							<?php printCustomMenu('zpBootstrap', 'list-top', '', 'active'); ?>
						<?php } else { ?>

							<?php if (getOption('zpB_homepage')) { ?>
								<li<?php if (isset($isHomePage)) { ?> class="active"<?php } ?>>
									<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Home'); ?>"><?php echo gettext('Home'); ?></a>
								</li>
							<?php } ?>

							<li<?php if ($galleryactive) { ?> class="active"<?php } ?>>
								<?php printCustomPageURL(gettext('Gallery'), 'gallery'); ?>
							</li>

							<?php if ($_zenpage_news_enabled && hasNews(true)) { ?>
								<li<?php if ($_gallery_page == 'news.php') { ?> class="active"<?php } ?>>
									<?php printNewsIndexURL(NEWS_LABEL, '', NEWS_LABEL); ?>
								</li>
							<?php } ?>

							<?php if ($_zenpage_pages_enabled) { ?>
								<?php printPageMenu('list-top', '', 'active', '', '', '', 0, false); ?>
							<?php } ?>
						<?php } ?>

						<?php if ((npg_loggedin()) && (extensionEnabled('favoritesHandler'))) { ?>
							<li<?php if ($_gallery_page == 'favorites.php') { ?> class="active"<?php } ?>>
								<?php printFavoritesURL(); ?>
							</li>
						<?php } ?>

						<?php if (extensionEnabled('contact_form')) { ?>
							<li<?php if ($_gallery_page == 'contact.php') { ?> class="active"<?php } ?>>
								<?php printCustomPageURL(gettext('Contact'), 'contact'); ?>
							</li>
						<?php } ?>

						<?php if (getOption('zpB_allow_search')) { ?>
							<li id="look"<?php if ($_gallery_page == 'archive.php') { ?> class="active"<?php } ?>>
								<a id="search-icon" class="text-center" href="<?php echo getCustomPageURL('archive'); ?>" title="<?php echo gettext('Search'); ?>"><span class="glyphicon glyphicon-search"></span></a>
							</li>
						<?php } ?>

						<?php if ((extensionEnabled('user_login-out')) && (!extensionEnabled('register_user'))) { ?>
							<?php if (npg_loggedin()) { ?>
								<li id="admin-single">
									<?php printUserLogin_out(); ?>
								</li>
							<?php } else { ?>
								<li id="admin-single">
									<a href="#login-modal" class="logonlink-single" data-toggle="modal" title="<?php echo gettext('Login'); ?>"></a>
								</li>
							<?php } ?>
						<?php } else if ((extensionEnabled('user_login-out')) || ((!npg_loggedin()) && (extensionEnabled('register_user')))) { ?>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle text-center" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon glyphicon-user"></span>&nbsp;&nbsp;<span class="glyphicon glyphicon-chevron-down"></span></a>
								<ul class="dropdown-menu">
									<?php if (extensionEnabled('user_login-out')) { ?>
										<?php if (npg_loggedin()) { ?>
											<li id="admin">
												<?php printUserLogin_out(); ?>
											</li>
										<?php } else { ?>
											<li id="admin">
												<a href="#login-modal" class="logonlink" data-toggle="modal" title="<?php echo gettext('Login'); ?>"><?php echo gettext('Login'); ?></a>
											</li>
										<?php } ?>
									<?php } ?>
									<?php if ((!npg_loggedin()) && (extensionEnabled('register_user'))) { ?>
										<li>
											<?php printRegisterURL(gettext('Register')); ?>
										</li>
									<?php } ?>
								</ul>
							</li>
						<?php } ?>

						<?php if (extensionEnabled('dynamic-locale')) { ?>
							<li id="flags" class="dropdown">
								<?php printLanguageSelector(); ?>
							</li>
						<?php } ?>
					</ul>
				</div><!--/.nav-collapse -->
			</div>
		</nav><!--/.navbar -->

		<?php if ((extensionEnabled('user_login-out')) && (!npg_loggedin()) && ($_gallery_page <> 'password.php') && ($_gallery_page <> 'register.php')) { ?>
			<div id="login-modal" class="modal" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-body">
							<?php printPasswordForm('', true, false); ?>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<!-- The scroll to top feature -->
		<div class="scroll-to-top">
			<span class="glyphicon glyphicon-chevron-up"></span>
		</div>

		<div id="main" class="container">
			<div class="page-header row">
				<?php if ((extensionEnabled('rss')) || (getOption('zpB_social_links'))) { ?>
					<div class="col-sm-push-9 col-sm-3">
						<?php
						if (extensionEnabled('rss')) {
							$rss = false;
							if ($_zenpage_news_enabled && (getOption('RSS_articles'))) {
								$rss = true;
								$type = 'News';
							} else if (getOption('RSS_album_image')) {
								$rss = true;
								$type = 'Gallery';
							}
							if ($rss) {
								?>
								<div class="feed pull-right">
									<?php printRSSLink($type, '', '', '', false, 'rss'); ?>
								</div>
								<script type="text/javascript">
									//<![CDATA[
									$('.rss').prepend('<img alt="RSS Feed" src="<?php echo $_themeroot; ?>/images/feed_icon.png">');
									//]]>
								</script>
							<?php } ?>
						<?php } ?>

						<?php if (getOption('zpB_social_links')) { ?>
							<div class="addthis pull-right">
								<!-- AddThis Button BEGIN -->
								<div class="addthis_toolbox addthis_default_style addthis_32x32_style">
									<a class="addthis_button_facebook"></a>
									<a class="addthis_button_twitter"></a>
									<!--<a class="addthis_button_favorites"></a>-->
									<a class="addthis_button_compact"></a>
								</div>
								<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js"></script>
								<!-- AddThis Button END -->
							</div>
						<?php } ?>
					</div>
				<?php } ?>

				<?php
				if ((extensionEnabled('rss')) || (getOption('zpB_social_links'))) {
					$col_header = ' col-sm-pull-3 col-sm-9';
				} else {
					$col_header = '';
				}
				?>

				<div class="header<?php echo $col_header; ?>">