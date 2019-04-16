<?php if (simpleMap::mapPlugin() == 'googleMap' && getOption('gmap_display') == 'colorbox') { ?>
	<div class="alert alert-danger"><?php echo gettext('The theme doesn\'t support colorbox option for googlemap plugin.'); ?></div>
	<?php
} else {
	$hasAlbumGeodata = false;
	$album = $_zp_current_album;
	$images = $album->getImages();

	foreach ($images as $an_image) {
		$image = newImage($album, $an_image);
		$lat = $image->get('GPSLatitude');
		$long = $image->get('GPSLongitude');
		if (!empty($lat) && !empty($long)) {
			$hasAlbumGeodata = true;
			break;
		}
	}

	// display map only if they are geodata
	if ($hasAlbumGeodata) {
		$gmap_display = 'gmap_show';
		if (simpleMap::mapPlugin() == 'googleMap' && getOption('gmap_display') == 'hide') {
			$gmap_display = 'gmap_hide';
			$hide = NULL;
		} else if (getOption('osmap_display') == 'hide') {
			$gmap_display = 'gmap_show';
			$hide = 'show';
		}
		?>
		<div id="gmap_accordion" class="panel-group" role="tablist">
			<div class="panel panel-default">
				<div id="gmap_heading" class="panel-heading" role="tab">
					<h4 class="panel-title">
						<?php
						if (simpleMap::mapPlugin() == 'googleMap') {
							if (getOption('gmap_display') == 'hide') {
								$gmap_display = 'gmap_hide';
							}
							$hide = NULL;
							?>
							<a id="<?php echo $gmap_display; ?>" data-toggle="collapse" data-parent="#gmap_accordion" href="#gmap_collapse_data">
								<span class="glyphicon glyphicon-map-marker"></span>&nbsp;<?php echo gettext('Map'); ?>
							</a>
							<?php
						} else {
							$hide = 'show'; //	the bootstrap stuff does not work for OpenStreetMap
							?>
							<span class="glyphicon glyphicon-map-marker"></span>&nbsp;<?php echo gettext('Map'); ?>
							<?php
						}
						?>
					</h4>
				</div>
			</div>
			<?php simpleMap::printMap(NULL, '', 'gmap_collapse', $hide); ?>
			<script type="text/javascript">
				//<![CDATA[
				$('#gmap_collapse_data').on('show.bs.collapse', function () {
					$('.hidden_map').removeClass('hidden_map');
				})
				//]]>
			</script>
		</div>
		<?php
	}
}
?>