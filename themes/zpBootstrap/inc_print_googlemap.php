			<?php if (getOption('gmap_display') == 'colorbox') { ?>
				<div class="alert alert-danger"><?php echo gettext('The theme doesn\'t support colorbox option for googlemap plugin.'); ?></div>
			<?php } else {
				$hasAlbumGeodata = false;
				$album = $_current_album;
				$images = $album->getImages();

				foreach ($images as $an_image) {
					$image = newImage($album, $an_image);
					$exif = $image->getMetaData();
					if ((!empty($exif['EXIFGPSLatitude'])) && (!empty($exif['EXIFGPSLongitude']))) {
						$hasAlbumGeodata = true; // at least one image has geodata
					}
				}

				// display map only if they are geodata
				if ($hasAlbumGeodata == true) {
					if (getOption('gmap_display') == 'hide') {
						$gmap_display = 'gmap_hide';
					} else if (getOption('gmap_display') == 'show') {
						$gmap_display = 'gmap_show';
					}
					?>
					<div id="gmap_accordion" class="panel-group" role="tablist">
						<div class="panel panel-default">
							<div id="gmap_heading" class="panel-heading" role="tab">
								<h4 class="panel-title">
									<a id="<?php echo $gmap_display; ?>" data-toggle="collapse" data-parent="#gmap_accordion" href="#gmap_collapse_data">
										<span class="glyphicon glyphicon-map-marker"></span>&nbsp;<?php echo gettext('Google Map'); ?>
									</a>
								</h4>
							</div>
						</div>
						<?php printGoogleMap('', 'gmap_collapse'); ?>
						<script type="text/javascript">
						//<![CDATA[
							;$('#gmap_collapse_data').on('show.bs.collapse', function () {
								$('.hidden_map').removeClass('hidden_map');
							})
						//]]>
						</script>
					</div>
					<?php
				}
			}
			?>