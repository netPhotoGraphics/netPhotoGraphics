<?php
/**
 * Image page personality
 */
// initialization stuff
$handler = new image_page();

class image_page {

	function __construct() {

	}

	function onePage() {
		return false;
	}

	function theme_head($_themeroot) {

	}

	function theme_bodyopen($_themeroot) {

	}

	function theme_content($map) {
		global $_current_image, $points;
		?>
		<!-- Image page section -->
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
					<div class="imagethumb"><a href="<?php echo html_encode(getImageURL()); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>"><?php printImageThumb(getImageTitle()); ?></a></div>
				</div>
				<?php
			}
			?>
		</div>
		<br class="clearall">
		<?php
		@call_user_func('printSlideShowLink');
	}

}
?>