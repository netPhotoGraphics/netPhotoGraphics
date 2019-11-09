			<?php
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
				
?>
				<div class="osm_panel panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title">
							<span class="glyphicon glyphicon-map-marker"></span>&nbsp;<?php echo gettext('Map'); ?>
						</h4>
					</div>
					<div class="panel-body">
						<?php printOpenStreetMap(); ?>
					</div>
				</div>
				<?php
			}
			?>