<?php

/**
 * Uses <code>openstreetmap</code> to create a map Track image from a
 * <b>GPX</b> file.
 *
 * This plugin provides an image handler for <b>GPX</b> files.
 * A GPX file, or GPS Exchange Format file, is an XML file that stores geographic
 * information like Waypoints, Tracks, and Routes.
 * The "image" shown will be the map Track defined by the file.
 *
 * The plugin supports an extension to the standard <b>GPX</b> file to optionally
 * set the color of the Track. To specify the Track color insure that <code>gpxx</code>
 * namespace is defined as below in your <gpx> head tag and add a
 * <code>gpxx:DisplayColor</code> extension to the Track section.
 *
 * <block>
 * <gpx <i>xmlns:gpxx="http://www.garmin.com/xmlschemas/GpxExtensions/v3"</i>>
 * 	...
 * 	<trk>
 * 		<name>GraphHopper Track</name>
 * 		<i><extensions>
 * 		 	<gpxx:TrackExtension>
 * 		 	 	<gpxx:DisplayColor>Red</gpxx:DisplayColor>
 * 		 	</gpxx:TrackExtension>
 * 		</extensions></i>
 * 		<trkseg>
 * 			<trkpt lat="47.009857" lon="-113.075981"><ele>1298.6</ele></trkpt>
 * 			...
 * 			<trkpt lat="47.025194" lon="-113.063088"><ele>1313.8</ele></trkpt>
 * 		</trkseg>
 * 	 </trk>
 * </gpx>
 * </block>
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/class-GPX
 * @pluginCategory media
 *
 */
$plugin_is_filter = 800 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Treats GPX files as "images" and shows the map Track defined by the file.');
	$plugin_disable = extensionEnabled('openstreetmap') ? '' : gettext('class-GPX requires the openStreetMap plugin be enabled.');
}

Gallery::addImageHandler('gpx', 'GPX');

require_once(__DIR__ . '/class-textobject/class-textobject_core.php');

class GPX extends TextObject_core {

	protected $GPXtrk = array();
	protected $trkname = '';
	protected $trkcolor;

	function __construct($album = NULL, $filename = NULL, $quiet = false) {

		if (is_object($album)) {
			parent::__construct($album, $filename, $quiet);

			$gpx = simplexml_load_file($this->localpath);

			if ($gpx !== false) {

				/* get track points */
				if (isset($gpx->trk)) {
					if (isset($gpx->trk->trkseg->trkpt)) {
						foreach ($gpx->trk->trkseg->trkpt as $trkpt) {
							$this->GPXtrk[] = array(
									'lat' => (string) $trkpt['lat'],
									'long' => (string) $trkpt['lon'],
									'title' => 'Track Point',
									'desc' => '',
									'thumb' => '',
									'current' => 0
							);
						}
					}
					if (isset($gpx->trk->name)) {
						$this->trkname = $gpx->trk->name;
					}
					if (is_object($gpx->trk->extensions->children('gpxx', true))) {
						$this->trkcolor = $gpx->trk->extensions->children('gpxx', true)->TrackExtension->DisplayColor;
					}
				} else if (isset($gpx->rte)) {
					foreach ($gpx->rte->rtept as $rtept) {
						$this->GPXtrk[] = array(// we will treate routes as if they were tracks
								'lat' => (string) $rtept['lat'],
								'long' => (string) $rtept['lon'],
								'title' => 'Track Point',
								'desc' => '',
								'thumb' => '',
								'current' => 0
						);
					}

					if (isset($gpx->rte->name)) {
						$this->trkname = $gpx->rte->name;
					}
					if (is_object($gpx->rte->extensions->children('gpxx', true))) {
						$this->trkcolor = $gpx->rte->extensions->children('gpxx', true)->TrackExtension->DisplayColor;
					}
				}
				if (empty($this->trkcolor)) {
					$this->trkcolor = 'blue';
				}
			} else {
				$this->exists = false;
				trigger_error(sprintf(gettext('%1$s: Failed to load GPX file.'), $this->displayname));
			}
		}
	}

	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array();
	}

	/**
	 * Returns the image file name for the thumbnail image.
	 *
	 * @return string
	 */
	function getThumbImageFile() {
		global $_gallery;
		if (is_null($this->objectsThumb)) {
			$img = '/' . getSuffix($this->filename) . 'Default.png';
			$imgfile = SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem($_gallery->getCurrentTheme()) . '/images/' . $img;
			if (!file_exists($imgfile)) {
				$imgfile = SERVERPATH . "/" . USER_PLUGIN_FOLDER . '/' . substr(basename(__FILE__), 0, -4) . $img;
				if (!file_exists($imgfile)) {
					$imgfile = SERVERPATH . "/" . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/' . substr(basename(__FILE__), 0, -4) . '/Default.png';
				}
			}
		} else {
			$imgfile = dirname($this->localpath) . '/' . $this->objectsThumb;
		}
		return $imgfile;
	}

	/**
	 * Returns the "image" html for the track
	 *
	 * @param type $w optional width
	 * @param type $h optional height
	 * @return type
	 */
	function getContent($w = NULL, $h = NULL) {
		if (empty($this->GPXtrk)) {
			trigger_error(sprintf(gettext('%1$s: No GPX path'), $this->displayname));
		}

		$this->updateDimensions();
		if (is_null($w)) {
			$w = $this->getWidth();
		}
		if (is_null($h)) {
			$h = $this->getHeight();
		}

		$map = new openStreetMap($this->GPXtrk, $this);

		$map->class = $map->mapid = 'osm_poly';
		$map->polycolor = $this->trkcolor;
		$map->mode = 'polyline-cluster';
		$map->hide = false;
		$map->width = $w . 'px';
		$map->height = $h . 'px';

		ob_start();
		$map->printMap();
		$img = ob_get_clean();

		return ($img);
	}

}
