<?php

/**
 * Creates map route images from GPX files.
 *
 * This plugin provides handlers for <b>GPX</b> files.
 * A GPX file, or GPS Exchange Format file, is an XML file that stores geographic
 * information like waypoints, tracks, and routes. The "image" shown will be the
 * map route defined by the file.
 *
 * Loads the openstreetmap plugin.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/class-GPX
 * @pluginCategory media
 *
 */
$plugin_is_filter = 800 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Treats GPX files as "images" and shows Map routes defined by the GPX file.');
}

$option_interface = '';

Gallery::addImageHandler('gpx', 'GPX');

require_once(__DIR__ . '/class-textobject/class-textobject_core.php');

class GPX extends TextObject_core {

	protected $GPXpath = array();
	protected $streetmap;

	function __construct($album = NULL, $filename = NULL, $quiet = false) {

		if (is_object($album)) {
			parent::__construct($album, $filename, $quiet);

			$gpx = simplexml_load_file($this->localpath);
			if ($gpx !== false) {
				if (isset($gpx->wpt)) {
					foreach ($gpx->wpt as $wpt) {
						$this->GPXpath[] = array(
								'lat' => (string) $wpt['lat'],
								'long' => (string) $wpt['lon'],
								'title' => !empty($wpt->name) ? (string) $wpt->name : 'Waypoint',
								'desc' => !empty($wpt->desc) ? (string) $wpt->desc : '',
								'thumb' => '',
								'current' => 0
						);
					}
				}

				if (isset($gpx->trk->trkseg->trkpt)) {
					foreach ($gpx->trk->trkseg->trkpt as $trkpt) {
						$this->GPXpath[] = array(
								'lat' => (string) $trkpt['lat'],
								'long' => (string) $trkpt['lon'],
								'title' => 'Track Point',
								'desc' => '',
								'thumb' => '',
								'current' => 0
						);
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

	function getContent($w = NULL, $h = NULL) {
		if (empty($this->GPXpath)) {
			trigger_error(sprintf(gettext('%1$s: No GPX path'), $this->displayname));
		}

		require_once(__DIR__ . '/openstreetmap.php');

		$start = reset($this->GPXpath);

//fool openStreetMap that the "image" has GPS coordinates
		$this->set('GPSLatitude', $start['lat']);
		$this->set('GPSLongitude', $start['long']);
		$map = new openStreetMap(null, $this);
//revert
		$this->set('GPSLatitude', null);
		$this->set('GPSLongitude', null);

		$map->class = $map->mapid = 'osm_poly';

		$map->polycolor = 'green'; //	somehow this should come from the GPX file

		$map->geodata = $this->GPXpath;
		$map->mode = 'polyline-cluster';
		$map->fitbounds = null; //openStreetMap caches this from the forced coordinates

		ob_start();
		$map->printMap();
		$img = ob_get_clean();
		return ($img);

		return('<span class="osm_path_image"></span>'); // so printDefaultSizedImage won't complain
	}

}
