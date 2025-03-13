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
 * set the color of the Track. To specify the Track color add a <code>color</code> tag to the
 * Track section:
 *
 * <block>
 * <trk>
 * 	<name>GraphHopper Track</name>
 *
 * 	<i><color>purple</color></i>
 *
 * 	<trkseg>
 * 		<trkpt lat="47.009857" lon="-113.075981"><ele>1298.6</ele></trkpt>
 * 		...
 * 		<trkpt lat="47.025194" lon="-113.063088"><ele>1313.8</ele></trkpt>
 * 	</trkseg>
 * </trk>
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
}

$option_interface = '';

Gallery::addImageHandler('gpx', 'GPX');

require_once(__DIR__ . '/class-textobject/class-textobject_core.php');

class GPX extends TextObject_core {

	protected $GPXtrk = array();
	protected $trkname = '';
	protected $trkcolor = 'blue';

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
					if (isset($gpx->trk->color)) {
						$this->trkcolor = $gpx->trk->color;
					}
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
	function getThumbImageFile($path = NULL) {
		global $_gallery;
		if (is_null($path)) {
			$path = SERVERPATH;
		}
		if (is_null($this->objectsThumb)) {
			$img = '/' . getSuffix($this->filename) . 'Default.png';
			$imgfile = $path . '/' . THEMEFOLDER . '/' . internalToFilesystem($_gallery->getCurrentTheme()) . '/images/' . $img;
			if (!file_exists($imgfile)) {
				$imgfile = $path . "/" . USER_PLUGIN_FOLDER . '/' . substr(basename(__FILE__), 0, -4) . $img;
				if (!file_exists($imgfile)) {
					$imgfile = $path . "/" . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/' . substr(basename(__FILE__), 0, -4) . '/Default.png';
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
	 * @param type $w
	 * @param type $h
	 * @return type
	 */
	function getContent($w = NULL, $h = NULL) {
		if (empty($this->GPXtrk)) {
			trigger_error(sprintf(gettext('%1$s: No GPX path'), $this->displayname));
		}

		require_once(__DIR__ . '/openstreetmap.php');

		$map = new openStreetMap($this->GPXtrk, $this);

		$map->class = $map->mapid = 'osm_poly';
		$map->polycolor = $this->trkcolor;
		$map->mode = 'polyline-cluster';

		ob_start();
		$map->printMap();
		$img = ob_get_clean();
		return ($img);
	}

}
