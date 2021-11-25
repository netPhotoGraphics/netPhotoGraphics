<?php
/**
 * This plugin is based on {@link https://github.com/terrymun/paver <b>Paver</b>} by terrymun
 *
 * The plugin will display exceptionally wide images as a scrollable panorama. Images which
 * fit into the image container will not be forced into panorama mode, so the function
 * can replace <code>printDefaultSizedImage()</code> in your <i>image.php</i> script.
 *
 * Typical usage:
 *
 * <code>&nbsp;&nbsp;if(class_exists('panorama') {	//	the plugin is enabled</code><br>
 * <code>&nbsp;&nbsp;&nbsp;&nbsp;panorama::image();</code><br>
 * <code>&nbsp;&nbsp;} else {</code><br>
 * <code>&nbsp;&nbsp;&nbsp;&nbsp;printDefaultSizedImage(getImageTitle());</code><br>
 * <code>&nbsp;&nbsp;}</code>
 *
 *
 * The <code>image<code> function has optional parameters: <i><code>alt&nbsp;text</code></i>, <i><code>class</code></i>,
 * <i><code>id</code></i>, and <i><code>image&nbsp;object</code></i>. if <i><code>image&nbsp;object</code></i> is not supplied the
 * current image object will be used. If <i><code>alt&nbsp;text</code></i> is not supplied the image
 * title will be used.
 *
 * <b>Note</b>: The <i>paver</i> scripts do not recognize nPG image processor URIs (e.g. i.php links.) This script will
 * attempt to in-line cache the panorama to avoide this deficiency but that may
 * not be possible if server memory is limited as the full compliment of theme scripts is loaded
 * before the caching can be requested. If the in-line caching fails, the first load of a
 * page with a particular panorama will not show the image. Typically refreshing the
 * browser will reload the page and fetch the cached image which will work properly.
 * If your images are large it would be a good idea to visit the image page to force caching.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/panorama
 * @pluginCategory media
 *
 * @Copyright 2020 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
$plugin_is_filter = 9 | THEME_PLUGIN;
$plugin_description = gettext('A plugin to display photo images as a panorama.');
$plugin_notice = gettext('Panoramic images can be quite large which may cause issues. Please review the note in the plugin usage information.');

$option_interface = 'panorama';

npgFilters::register('theme_head', 'panorama::head');
npgFilters::register('theme_body_close', 'panorama::close');

class panorama {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('panorama_height', 400);
			setOptionDefault('panorama_start', 50);
		}
	}

	function getOptionsSupported() {
		return array(
				gettext('Panorama height') => array('key' => 'panorama_height', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'desc' => sprintf(gettext('The image will be resized so that its <span style="color:blue">%1$s</span> pixels high when displayed. The image width will be scaled proportionately.'), getOption('panorama_height'))),
				gettext('Start position') => array('key' => 'panorama_start', 'type' => OPTION_TYPE_CUSTOM,
						'limits' => array('min' => 0, 'max' => 100, 'step' => 1),
						'order' => 2,
						'desc' => gettext('Indicate the start position of the panorama by positioning the slider.'))
		);
	}

	function handleOption($key, $v) {
		putSlider('<span style="float:left">' . gettext('image left') . '</span><span style="float:right">' . gettext('image right') . '</span><br />', $key, 0, 100, $v, FALSE);
	}

	static function head() {
		scriptLoader(PLUGIN_SERVERPATH . 'panorama/paver.css');
	}

	static function close() {
		scriptLoader(PLUGIN_SERVERPATH . 'panorama/jquery.paver.min.js');
		scriptLoader(PLUGIN_SERVERPATH . 'panorama/jquery.ba-throttle-debounce.min.js');
		?>
		<style>
			.nPG_panorama {
				overflow-x: hidden;
				overflow-y: hidden;
			}
		</style>
		<script type="text/javascript">
			$(document).ready(function () {
				$(function () {
					// Paver
					$('div.nPG_panorama').paver({
						failureMessage: '<?php echo gettext('Scroll left/right to pan through panorama.'); ?>',
						minimumOverflow: 0,
						startPosition: <?php echo getOption('panorama_start') / 100; ?>
					});
				});
			});
		</script>
		<?php
	}

	static function image($title = NULL, $image = NULL) {
		global $_current_image, $_gallery;
		if (is_null($image)) {
			$image = $_current_image;
		}
		if (is_null($image)) {
			return false;
		}
		if ($image->isPhoto()) {
			if (empty($title)) {
				$title = $image->getTitle();
			}

			$h = $image->getHeight();
			$w = $image->getWidth();
			$height = getOption('panorama_height');
			$width = (int) ($height / $h * $w);
			$img_link = $image->getCustomImage(array('width' => $width, 'height' => $height));
			if (strpos($img_link, '/' . CORE_FOLDER . '/i.php') !== FALSE) {
				//	i.php link, cache the image because paver fails if it is not already cached
				require_once(dirname(__DIR__) . '/lib-image.php');
				imageProcessing::cacheFromImageProcessorURI($img_link);
				$img_link = $image->getCustomImage(array('width' => $width, 'height' => $height));
			}
			?>
			<div class="nPG_panorama" data-paver>
				<?php
				$html = '<img src="' . $img_link . '" width=' . $width . '; height=' . $height . '; alt="' . $title . '" />';
				//	note: paver sets the image to a background URL, so lazyLoading does not apply
				//	but maybe other filters would, so we apply the filter anyway.
				echo npgFilters::apply('custom_image_html', $html, FALSE);
				?>
			</div>

			<?php
		} else {
			echo $image->getContent();
		}
	}

}
