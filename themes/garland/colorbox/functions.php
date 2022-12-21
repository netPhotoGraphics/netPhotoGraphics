<?php
/**
 * Colorbox personality
 */
// initialization stuff

if (npgFilters::has_filter('theme_head', 'colorbox::css')) {
	$handler = new ga_colorbox();
} else {
	require_once(__DIR__ . '/image_page/functions.php');
}

class ga_colorbox {

	function __construct() {

	}

	function theme_head($_themeroot) {
		?>
		<script type="text/javascript">
			
			window.addEventListener('load', function () {
			$("a.thickbox").colorbox({
			maxWidth: "98%",
							maxHeight: "98%",
							photo: true,
							close: '<?php echo gettext("close"); ?>'
							onComplete: function(){
							$(window).resize(resizeColorBoxImage);
							}
			});
			}, false);
			
		</script>
		<?php
	}

	function theme_bodyopen($_themeroot) {

	}

	function theme_content($map) {
		global $_current_image, $points;
		?>
		<!-- Colorbox section -->
		<div id="images">
			<?php
			$points = array();
			while (next_image()) {
				if ($map) {
					$coord = simpleMap::getCoord($_current_image);
					if ($coord) {
						$points[] = $coord;
					}
				}
				?>
				<div class="image">
					<div class="imagethumb">
						<?php
						if ($_current_image->isPhoto()) {
							// colorbox is only for real images
							$link = html_encode(getDefaultSizedImage()) . '" class="thickbox"';
						} else {
							$link = html_encode(getImageURL()) . '"';
						}
						?>
						<a href="<?php echo $link; ?>" title="<?php echo html_encode(getBareImageTitle()); ?>">
							<?php printImageThumb(getImageTitle()); ?>
						</a></div>
				</div>
				<?php
			}
			?>
		</div>
		<br class="clearall" />
		<?php
		if (function_exists('printSlideShowLink'))
			printSlideShowLink();
	}

}
?>