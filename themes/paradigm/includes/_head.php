

<html lang="<?php echo getOption('locale'); ?>">

	<head>

		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,400italic,700,300,600' rel='stylesheet' type='text/css'>

		<?php $searchwords = getSearchWords(); ?>

		<!-- meta -->

		<meta http-equiv="content-type" content="text/html; charset=<?php echo LOCAL_CHARSET; ?>" />

		<title><?php
			switch ($_gallery_page) {
				case 'index.php':
					echo getGalleryTitle();
					echo ' | ';
					break;
				case 'album.php':
					echo getBareAlbumTitle();
					if ($_current_page > 1) {
						echo ' [' . $_current_page . ']';
					}echo ' | ';
					break;
				case 'gallery.php':
					echo gettext('Gallery');
					if ($_current_page > 1) {
						echo ' [' . $_current_page . ']';
					}echo ' | ';
					break;
				case '404.php':
					echo gettext('Object not found');
					echo ' | ';
					break;
				case 'archive.php':
					echo gettext('Archive View');
					echo ' | ';
					break;
				case 'summary.php':
					echo gettext('Daily summary');
					echo ' | ';
					break;
				case 'contact.php':
					echo gettext('Contact');
					echo ' | ';
					break;
				case 'favorites.php':
					echo gettext('My favorites');
					if ($_current_page > 1) {
						echo ' [' . $_current_page . ']';
					}echo ' | ';
					break;
				case 'image.php':
					echo getBareImageTitle() . ' - photo from ' . getBareAlbumTitle();
					echo ' | ';
					break;
				case 'news.php':
					if ((is_NewsPage()) && !is_NewsCategory() && !is_NewsArticle()) {
						echo gettext(NEWS_LABEL);
						echo ' | ';
					}
					if (is_NewsCategory()) {
						printCurrentNewsCategory();
						echo ' | ';
						echo gettext('Blog');
						echo ' | ';
					}
					if (is_NewsArticle()) {
						echo getBareNewsTitle();
						echo ' | ';
						echo gettext('Blog');
						echo ' | ';
					}
					break;
				case 'pages.php':
					echo getBarePageTitle();
					echo ' | ';
					break;
				case 'password.php':
					echo gettext('Password required');
					echo ' | ';
					break;
				case 'register.php':
					echo gettext('Register');
					echo ' | ';
					break;
				case 'credits.php':
					echo gettext('Credits');
					echo ' | ';
					break;
				case 'search.php':
					echo html_encode($searchwords);
					echo ' | ';
					break;
			}
			echo getMainSiteName();
			?>
		</title>

		<?php
		if (isset($_GET["page"]) && $_gallery_page == 'search.php') {
			echo '<meta name="robots" content="noindex, nofollow">';
		} elseif (isset($_GET["page"]) && $_gallery_page != 'search.php' || $_gallery_page == 'archive.php' || $_gallery_page == 'favorites.php' || $_gallery_page == 'password.php' || $_gallery_page == 'register.php' || $_gallery_page == 'contact.php') {
			echo '<meta name="robots" content="noindex, follow">';
		} else {
			echo '<meta name="robots" content="index, follow">';
		}
		?>


		<!-- Open Graph -->

		<meta property="og:title" content="<?php
		if (($_gallery_page == 'index.php')) {
			echo gettext('Home') . ' | ';
		}
		if ($_gallery_page == 'album.php') {
			echo getBareAlbumTitle();
			if ($_current_page > 1) {
				echo ' [' . $_current_page . ']';
			}echo ' | ';
		}
		if ($_gallery_page == 'gallery.php') {
			echo gettext('Albums');
			if ($_current_page > 1) {
				echo ' [' . $_current_page . ']';
			}echo ' | ';
		}
		if ($_gallery_page == '404.php') {
			echo gettext('Object not found');
			echo ' | ';
		}
		if ($_gallery_page == 'archive.php') {
			echo gettext('Archive View');
			echo ' | ';
		}
		if ($_gallery_page == 'contact.php') {
			echo gettext('Contact');
			echo ' | ';
		}
		if ($_gallery_page == 'favorites.php') {
			echo gettext('My favorites');
			if ($_current_page > 1) {
				echo ' [' . $_current_page . ']';
			}echo ' | ';
		}
		if ($_gallery_page == 'image.php') {
			echo getBareImageTitle() . ' | ' . getBareAlbumTitle();
			echo ' | ';
		}
		if (($_gallery_page == 'news.php') && (is_NewsPage()) && (!is_NewsCategory()) && (!is_NewsArticle())) {
			echo gettext('Blog');
			echo ' | ';
		}
		if (($_gallery_page == 'news.php') && (is_NewsCategory())) {
			printCurrentNewsCategory();
			echo ' | ';
			echo gettext('Blog');
			echo ' | ';
		}
		if (($_gallery_page == 'news.php') && (is_NewsArticle())) {
			echo getBareNewsTitle();
			echo ' | ';
			echo gettext('Blog');
			echo ' | ';
		}
		if ($_gallery_page == 'pages.php') {
			echo getBarePageTitle();
			echo ' | ';
		}
		if ($_gallery_page == 'password.php') {
			echo gettext('Password required');
			echo ' | ';
		}
		if ($_gallery_page == 'register.php') {
			echo gettext('Register');
			echo ' | ';
		}
		if ($_gallery_page == 'search.php') {
			echo gettext('Search');
			if ($_current_page > 1) {
				echo ' [' . $_current_page . ']';
			} echo ' | ';
		}
		echo getMainSiteName();
		?>" />
		<meta property="og:type" content="article" />
		<meta property="og:url" content="<?php echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]); ?>" />
		<?php
		if ($_gallery_page == 'image.php' && isImagePhoto()) {
			echo '<meta property="og:image" content="';
			echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
			echo (getDefaultSizedImage());
			echo '" />
';
		}
		if ($_gallery_page == 'image.php' && !isImagePhoto()) {
			echo '<meta property="og:image" content="';
			echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
			echo (getImageThumb());
			echo '" />
';
		}
		if ($_gallery_page == 'album.php') {
			echo '<meta property="og:image" content="';
			echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
			echo getCustomAlbumThumb(Null, 650, 650);
			;
			echo '" />
';
		}
		if ($_gallery_page == 'index.php') {
			echo '<meta property="og:image" content="';
			echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
			echo $_themeroot;
			echo '/img/logo.png" />
';
		}
		?>
		<?php
		if (($_gallery_page == 'image.php') && getBareImageDesc() != '') {
			echo '<meta property="og:description" content="';
			echo getBareImageDesc();
			echo '"/>';
		}
		if (($_gallery_page == 'album.php') && getBareAlbumDesc() != '') {
			echo '<meta property="og:description" content="';
			echo getBareAlbumDesc();
			echo '"/>';
		}
		?>
		<meta property="og:site_name" content="<?php echo getMainSiteName(); ?>" />


		<!-- twitter cards -->
		<?php if (($_gallery_page == 'index.php')) { ?>
			<meta name="twitter:card" content="summary" />
			<meta name="twitter:site" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:title" content="<?php
			echo gettext('Home') . ' | ';
			echo getMainSiteName();
			?>" />
			<meta name="twitter:description" content="<?php
			echo getGalleryTitle();
			echo ' #netPhotoGraphics ';
			echo gettext('album')
			?>"  />
			<meta name="twitter:url" content="<?php echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]); ?>" />
		<?php } ?>
		<?php if (($_gallery_page == 'image.php') && isImagePhoto()) { ?>
			<meta name="twitter:card" content="summary_large_image" />
			<meta name="twitter:site" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:creator" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:title" content="<?php printImageTitle(); ?>" />
			<meta name="twitter:description" content="<?php echo getBareImageDesc(); ?>" />
			<meta name="twitter:url" content="<?php echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]); ?>" />
			<meta name="twitter:image" content="<?php
			echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
			echo (getDefaultSizedImage());
			?>" />
					<?php } ?>
					<?php if (($_gallery_page == 'image.php') && !isImagePhoto()) { ?>
			<meta name="twitter:card" content="summary_large_image" />
			<meta name="twitter:site" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:creator" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:title" content="<?php printImageTitle(); ?>" />
			<meta name="twitter:description" content="<?php printImageDesc(); ?>" />
			<meta name="twitter:url" content="<?php echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]); ?>" />
			<meta name="twitter:image" content="<?php
			echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
			echo (getImageThumb());
			?>" />
					<?php } ?>
					<?php if ($_gallery_page == 'album.php') { ?>
			<meta name="twitter:card" content="summary_large_image" />
			<meta name="twitter:site" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:creator" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:title" content="<?php
			printAlbumTitle();
			echo (' ');
			echo gettext('album');
			?>" />
			<meta name="twitter:description" content="<?php echo getBareAlbumDesc(); ?>" />
			<meta name="twitter:url" content="<?php echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]); ?>" />
			<meta name="twitter:image" content="<?php
			echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
			echo getCustomAlbumThumb(Null, 650, 650);
			?>" />
					<?php } ?>
					<?php if ((($_gallery_page == 'news.php') && (is_NewsArticle()))) { ?>
			<meta name="twitter:card" content="summary" />
			<meta name="twitter:site" content="<?php
			if (getOption('twitter_profile') != '') {
				echo '@';
				echo getOption('twitter_profile');
			}
			?>"/>
			<meta name="twitter:title" content="<?php echo getBareNewsTitle() ?>" />
			<meta name="twitter:url" content="<?php echo (PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]); ?>" />

		<?php } ?>


		<!-- css -->
		<?php
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/common/bootstrap/bootstrap.min.css');
		scriptLoader($_themeroot . '/css/site.css');
		scriptLoader($_themeroot . '/css/icons.css');
		scriptLoader($_themeroot . '/css/slimbox2.css');
		?>

		<!-- favicon -->

		<link rel="shortcut icon" href="<?php echo $_themeroot; ?>/img/favicon.ico">


		<!-- js -->
		<?php
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/common/bootstrap/bootstrap.min.js');
		scriptLoader($_themeroot . '/js/slimbox2-ar.js');
		?>

		<!-- rss -->

		<?php if (class_exists('RSS')) printRSSHeaderLink('Gallery', gettext('Gallery')); ?>

		<?php npgFilters::apply('theme_head'); ?>


		<!-- Analytics -->

		<?php if (getOption('analytics_code') != '') { ?>
			<script>
				(function (i, s, o, g, r, a, m) {
					i['GoogleAnalyticsObject'] = r;
					i[r] = i[r] || function () {
						(i[r].q = i[r].q || []).push(arguments)
					}, i[r].l = 1 * new Date();
					a = s.createElement(o),
									m = s.getElementsByTagName(o)[0];
					a.async = 1;
					a.src = g;
					m.parentNode.insertBefore(a, m)
				})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

				ga('create', '<?php echo getOption('analytics_code'); ?>', 'auto');
				ga('set', 'contentGroup1', '<?php
		if (($_gallery_page == 'index.php')) {
			echo '00 homepage';
		}
		if ($_gallery_page == 'album.php') {
			echo '20 album';
		}
		if ($_gallery_page == 'gallery.php') {
			echo '00 gallery-homepage';
		}
		if ($_gallery_page == '404.php') {
			echo '90 error';
		}
		if ($_gallery_page == 'archive.php') {
			echo '80 utility';
		}
		if ($_gallery_page == 'contact.php') {
			echo '80 utility';
		}
		if ($_gallery_page == 'favorites.php') {
			echo '80 utility';
		}
		if ($_gallery_page == 'image.php') {
			echo '30 image';
		}
		if (($_gallery_page == 'news.php') && (!is_NewsArticle())) {
			echo '40 news list';
		}
		if (($_gallery_page == 'news.php') && (is_NewsArticle())) {
			echo '45 news';
		}
		if (($_gallery_page == 'pages.php') || ($_gallery_page == 'credits.php')) {
			echo '10 page';
		}
		if ($_gallery_page == 'password.php') {
			echo '80 utility';
		}
		if ($_gallery_page == 'register.php') {
			echo '80 utility';
		}
		if ($_gallery_page == 'search.php') {
			echo '50 tag';
		}
		?>');
				ga('set', 'contentGroup2', '<?php
		if ($_gallery_page == 'album.php') {
			echo getAlbumTitle();
		}
		if ($_gallery_page == 'image.php') {
			echo getAlbumTitle();
		}
		if ($_gallery_page == 'news.php') {
			echo printCurrentNewsCategory();
		}
		?>');
				ga('send', 'pageview');

			</script>
		<?php } ?>

	</head>