<?php
/**
 * A simple plugin that enabled touch screen left and right swiping for various previous and next pages.
 * Based on the jQuery plugin  {@link http://labs.rampinteractive.co.uk/touchSwipe touchSwipe}
 *
 * @author Malte Müller (acrylian) <info@maltem.de> modified for netPhotoGraphics by Stephen Billard (sbillard)
 * @copyright 2015 Malte Müller, 2020 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * @license GPL v3 or later
 * @package plugins/swipe_gestures
 * @pluginCategory theme
 */
$plugin_is_filter = 9 | THEME_PLUGIN;
$plugin_description = gettext('Enable touch screen left/right swiping to move to the next/previous page.');

$option_interface = 'swipeGestures';

npgFilters::register('theme_body_close', 'swipeGestures::swipejs');

/**
 * Plugin option handling class
 *
 */
class swipeGestures {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('swipe_gestures_threshold', 75);
			setOptionDefault('swipe_gestures_image', 1);
			setOptionDefault('swipe_gestures_album', 1);
			setOptionDefault('swipe_gestures_news', 1);
		}
	}

	function getOptionsSupported() {
		$options = array(
				gettext('Threshold') => array(
						'key' => 'swipe_gestures_threshold',
						'type' => OPTION_TYPE_NUMBER,
						'desc' => gettext('The threshold swipe distance distance (pixels) to triger a swipe.')),
				gettext('Single image page') => array(
						'key' => 'swipe_gestures_image',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Enables left/right swipe gestures for the previous/next image navigation.')),
				gettext('Album pages') => array(
						'key' => 'swipe_gestures_album',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Enables left/right swipe gestures for the previous/next album/search page navigation.')),
				gettext('News pages') => array(
						'key' => 'swipe_gestures_news',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Enables left/right swipe gestures for the previous/next news loop page navigation (<em>news on index</em> option not supported).'))
		);
		return $options;
	}

	static function swipejs() {
		global $_gallery, $_current_image, $_gallery_page;

		$prevurl = '';
		$nexturl = '';
		switch ($_gallery_page) {
			case 'index.php':
			case 'gallery.php':
			case 'album.php':
			case 'search.php':
				if (getOption('swipe_gestures_album')) {
					if (hasPrevPage()) {
						$prevurl = getPrevPageURL();
					}
					if (hasNextPage()) {
						$nexturl = getNextPageURL();
					}
				}
				break;
			case 'image.php':
				if (getOption('swipe_gestures_image')) {
					if (hasPrevImage()) {
						$prevurl = getPrevImageURL();
					}
					if (hasNextImage()) {
						$nexturl = getNextImageURL();
					}
				}
				break;
			case 'news.php':
				if (getOption('swipe_gestures_news') && getOption('zp_plugin_zenpage')) {
					if (is_NewsArticle()) {
						if (getPrevNewsURL()) {
							$prevurl = getPrevNewsURL();
							$prevurl = $prevurl['link'];
						}
						if (getNextNewsURL()) {
							$nexturl = getNextNewsURL();
							$nexturl = $nexturl['link'];
						}
					} else {
						if (getPrevNewsPageURL()) {
							$prevurl = getPrevNewsPageURL();
						}
						if (getNextNewsPageURL()) {
							$nexturl = getNextNewsPageURL();
						}
					}
				}
				break;
		}
		if (!empty($prevurl) || !empty($nexturl)) {
			scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/swipe_gestures/jquery.touchSwipe.min.js');
			?>
			<script type="text/javascript">
				$('html').swipe({
			<?php if (!empty($prevurl)) { ?>
					swipeRight:function(event, direction, distance, duration, fingerCount) {
					this.fadeOut();
					document.location.href = '<?php echo $prevurl; ?>';
					},
			<?php } ?>
			<?php if (!empty($nexturl)) { ?>
					swipeLeft:function(event, direction, distance, duration, fingerCount) {
					this.fadeOut();
					document.location.href = '<?php echo $nexturl; ?>';
					},
			<?php } ?>
				threshold: <?php echo getOption('swipe_gestures_threshold'); ?>
				excludedElements: "label, button, input, select, textarea"
				});
			</script>
			<?php
		}
	}

}
?>
