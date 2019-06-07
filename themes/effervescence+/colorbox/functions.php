<?php
/**
 * Colorbox personality
 */
// initialization stuff

if (npgFilters::has_filter('theme_head', 'colorbox::css')) {
	$handler = new ef_colorbox();
} else {
	require_once(SERVERPATH . '/' . THEMEFOLDER . '/effervescence+/image_page/functions.php');
}

class ef_colorbox {

	function __construct() {

	}

	function onePage() {
		return false;
	}

	function theme_head($_themeroot) {
		?>
		<script type="text/javascript">
			// <!-- <![CDATA[
			window.addEventListener('load', function () {
				$("a.thickbox").colorbox({
					maxWidth: "98%",
					maxHeight: "98%",
					photo: true,
					close: '<?php echo gettext("close"); ?>'
				});
			}, false);
			// ]]> -->
		</script>
		<?php
	}

	function theme_bodyopen($_themeroot) {

	}

	function theme_content($map) {
		global $_current_image, $points;
		?>
		<!-- Colorbox section -->
		<div id="content">
			<div id="main">
				<div id="images">
					<?php
					$points = array();
					$firstImage = null;
					$lastImage = null;
					while (next_image()) {
						// Colorbox does not do video
						if (is_null($firstImage)) {
							$lastImage = imageNumber();
							$firstImage = $lastImage;
						} else {
							$lastImage++;
						}
						?>
						<div class="image">
							<div class="imagethumb">
								<?php
								if ($map) {
									$coord = simpleMap::getCoord($_current_image);
									if ($coord) {
										$points[] = $coord;
									}
								}
								$annotate = annotateImage();
								if (isImagePhoto()) {
									// colorbox is only for real images
									echo '<a href="' . html_encode(getDefaultSizedImage()) . '" class="thickbox"';
								} else {
									echo '<a href="' . html_encode(getImageURL()) . '"';
								}
								echo " title=\"" . html_encode($annotate) . "\">\n";
								printImageThumb($annotate);
								echo "</a>";
								?>
							</div>
						</div>
						<?php
					}
					echo '<div class="clearage"></div>';
					if (!empty($points) && $map) {
						?>
						<div id="map_link">
							<?php simpleMap::printMap($points, array('obj' => 'album_page')); ?>
						</div>
						<?php
					}
					@call_user_func('printSlideShowLink', NULL, 'text-align:center;');
					?>
				</div><!-- images -->
				<?php @call_user_func('printRating'); ?>
			</div><!-- main -->
			<div class="clearage"></div>
			<?php if (isset($firstImage)) printNofM('Photo', $firstImage, $lastImage, getNumImages()); ?>
		</div><!-- content -->
		<?php
	}

}
?>