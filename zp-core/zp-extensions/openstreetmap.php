<?php
/**
 * A plugin for showing OpenStreetMap maps using {@link http://leafletjs.com LeafletJS} for images, images from
 * albums with embeded geodata, or from custom geodata. To invoke add <code>printOpenStreetmap()</code> to your
 * image and album scripts.
 *
 * Also includes
 *
 * <ul>
 * <li>{@link https://github.com/Leaflet/Leaflet.markercluster Marker cluster} plugin by Dave Leaver</li>
 * <li>{@link https://github.com/ardhi/Leaflet.MousePosition MousePosition} plugin by Ardhi Lukianto</li>
 * <li>{@link https://github.com/Norkart/Leaflet-MiniMap Leaflet-MiniMap} plugin</li>
 * <li>{@link https://github.com/leaflet-extras/leaflet-providers leaflet-providers} plugin</li>
 * </ul>
 *
 * @author Malte Müller (acrylian), Fred Sondaar (fretzl), gjr, Vincent Bourganel (vincent3569), Stephen Billard (netPhotoGraphics adaption)
 * @licence GPL v3 or later
 * @package plugin/openstreetmap
 * @pluginCategory theme
 */
$plugin_is_filter = 5 | THEME_PLUGIN;
$plugin_description = gettext("A plugin for displaying OpenStreetMap based maps.");

$option_interface = 'openStreetMapOptions';

npgFilters::register('theme_head', 'openStreetMap::scripts');

class openStreetMapOptions {

	function __construct() {
		/* clean up old options */
		if (OFFSET_PATH == 2) {
			replaceOption('osmap_controlpos', 'osmap_zoomcontrolpos', 'topleft');
			replaceOption('osmap_maptiles', 'osmap_defaultlayer', 'OpenStreetMap.Mapnik');

			setOptionDefault('osmap_width', '100%'); //responsive by default!
			setOptionDefault('osmap_height', '300px');
			setOptionDefault('osmap_zoom', 4);
			setOptionDefault('osmap_minzoom', 2);
			setOptionDefault('osmap_maxzoom', 18);
			setOptionDefault('osmap_clusterradius', 40);
			setOptionDefault('osmap_markerpopup_title', 1);
			setOptionDefault('osmap_markerpopup_desc', 1);
			setOptionDefault('osmap_markerpopup_thumb', 1);
			setOptionDefault('osmap_showlayerscontrol', 0);
			setOptionDefault('osmap_layerscontrolpos', 'topright');
			foreach (openStreetMap::$tileProviders as $layer_dbname) {
				setOptionDefault($layer_dbname, 0);
			}
			setOptionDefault('osmap_showscale', 1);
			setOptionDefault('osmap_showalbummarkers', 0);
			setOptionDefault('osmap_showminimap', 0);
			setOptionDefault('osmap_minimap_width', 100);
			setOptionDefault('osmap_minimap_height', 100);
			setOptionDefault('osmap_minimap_zoom', -5);
			setOptionDefault('osmap_cluster_showcoverage_on_hover', 0);
			setOptionDefault('osmap_display', 'show');

			if (class_exists('cacheManager')) {
				cacheManager::deleteCacheSizes('openstreetmap');
				cacheManager::addCacheSize('openstreetmap', 150, NULL, NULL, NULL, NULL, NULL, NULL, true, NULL, NULL, NULL);
			}
		}
	}

	function getOptionsSupported() {
		$layerslist = openStreetMap::$tileProviders;
		$options = array(
				gettext('Map dimensions—width') => array(
						'key' => 'osmap_width',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'desc' => gettext("Width of the map including the unit name e.g 100% (default for responsive map), 100px or 100em.")),
				gettext('Map dimensions—height') => array(
						'key' => 'osmap_height',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext("Height of the map including the unit name e.g 100% (default for responsive map), 100px or 100em.")),
				gettext('Map zoom') => array(
						'key' => 'osmap_zoom',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 3,
						'desc' => gettext("Default zoom level.")),
				gettext('Map minimum zoom') => array(
						'key' => 'osmap_minzoom',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 5,
						'desc' => gettext("Default minimum zoom level possible.")),
				gettext('Map maximum zoom') => array(
						'key' => 'osmap_maxzoom',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 6,
						'desc' => gettext("Default maximum zoom level possible. If no value is defined, use the maximum zoom level of the map used (may be different for each map).")),
				gettext('Map display') => array('key' => 'osmap_display', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 6.5,
						'selections' => array(gettext('show') => 'show',
								gettext('hide') => 'hide',
								gettext('colorbox') => 'colorbox'),
						'desc' => gettext('Select <em>hide</em> to initially hide the map. Select <em>show</em> and the map will display when the page loads.')),
				gettext('Default layer') => array(
						'key' => 'osmap_defaultlayer',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 7,
						'selections' => array_combine(array_keys($layerslist), array_keys($layerslist)),
						'desc' => gettext('The default map tile provider to use. Only free providers are included.'
										. ' Some providers (Here, Mapbox, Thunderforest, Geoportail) require access credentials and registration.'
										. ' More info on <a href="https://github.com/leaflet-extras/leaflet-providers">leaflet-providers</a>')),
				gettext('Zoom controls position') => array(
						'key' => 'osmap_zoomcontrolpos',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 8,
						'selections' => array(
								gettext('Top left') => 'topleft',
								gettext('Top right') => 'topright',
								gettext('Bottom left') => 'bottomleft',
								gettext('Bottom right') => 'bottomright'
						),
						'desc' => gettext('Position of the zoom controls')),
				gettext('Cluster radius') => array(
						'key' => 'osmap_clusterradius',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 9,
						'desc' => gettext("The maximum radius that a cluster will cover from the central marker (in pixels). Decreasing will make more, smaller clusters.")),
				gettext('Show cluster coverage on hover') => array(
						'key' => 'osmap_cluster_showcoverage_on_hover',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 10,
						'desc' => gettext("Enable if you want to show the bounds of a marker cluster on hover.")),
				gettext('Marker popups') => array(
						'key' => 'osmap_markerpopup',
						'type' => OPTION_TYPE_CHECKBOX_ARRAY,
						'checkboxes' => array(
								gettext('Thumb') => 'osmap_markerpopup_thumb',
								gettext('Title') => 'osmap_markerpopup_title',
								gettext('Description') => 'osmap_markerpopup_desc'
						),
						'order' => 12,
						'desc' => gettext("Enable the popups you want shown. Popups occur only in the <em>album</em> context.")),
				gettext('Show layers controls') => array(
						'key' => 'osmap_showlayerscontrol',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 14.2,
						'desc' => gettext("Enable if you want to show layers controls with selected layers list below.")),
				gettext('Layers list') => array(
						'key' => 'osmap_layerslist',
						'type' => OPTION_TYPE_CHECKBOX_UL,
						'order' => 14.4,
						'checkboxes' => $layerslist,
						'desc' => gettext('Choose layers list to show in layers controls. You can preview the layers <a href="http://leaflet-extras.github.io/leaflet-providers/preview/index.html">here</a>.')),
				gettext('Layers controls position') => array(
						'key' => 'osmap_layerscontrolpos',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 14.6,
						'selections' => array(
								gettext('Top left') => 'topleft',
								gettext('Top right') => 'topright',
								gettext('Bottom left') => 'bottomleft',
								gettext('Bottom right') => 'bottomright'
						),
						'desc' => gettext('Position of the layers controls')),
				gettext('Show scale') => array(
						'key' => 'osmap_showscale',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 15,
						'desc' => gettext("Enable if you want to show scale overlay (kilometers and miles).")),
				gettext('Show cursor position') => array(
						'key' => 'osmap_showcursorpos',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 16,
						'desc' => gettext("Enable if you want to show the coordinates if moving the cursor over the map.")),
				gettext('Show album markers') => array(
						'key' => 'osmap_showalbummarkers',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 17,
						'desc' => gettext("Enable if you want to show the map on the single image page not only the marker of the current image but all markers from the album. The current position will be highlighted.")),
				gettext('Mini map') => array(
						'key' => 'osmap_showminimap',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 18,
						'desc' => gettext("Enable if you want to show an overview mini map in the lower right corner.")),
				gettext('Mini map: width') => array(
						'key' => 'osmap_minimap_width',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 19,
						'desc' => gettext("Pixel width")),
				gettext('Mini map: height') => array(
						'key' => 'osmap_minimap_height',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 20,
						'desc' => gettext("Pixel height")),
				gettext('Mini map: Zoom level') => array(
						'key' => 'osmap_minimap_zoom',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 21,
						'desc' => gettext("The offset applied to the zoom in the minimap compared to the zoom of the main map. Can be positive or negative, defaults to -5.")),
				gettext('HERE - App id') => array(
						'key' => 'osmap_here_appid',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 22,
						'desc' => ''),
				gettext('HERE - App code') => array(
						'key' => 'osmap_here_appcode',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 23,
						'desc' => ''),
				gettext('Mapbox - Access token') => array(
						'key' => 'osmap_mapbox_accesstoken',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 24,
						'desc' => ''),
				gettext('Thunderforest - ApiKey') => array(
						'key' => 'osmap_thunderforest_apikey',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 25,
						'desc' => ''),
				gettext('GeoportailFrance - ApiKey') => array(
						'key' => 'osmap_geoportailfrance_apikey',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 26,
						'desc' => ''),
		);
		return $options;
	}

}

/**
 * The class for all OSM map related functionality
 */
class openStreetMap {

	/**
	 * Contains the array of the image or images from albums geodata
	 * @var array
	 */
	var $geodata = NULL;

	/**
	 * Contains a string presenting a Javascript array of geodata for leafletjs
	 * @var array
	 */
	var $geodatajs = NULL;

	/**
	 * geodata array('min' => array(lat,lng), 'max => array(lat,lng))
	 * Default created from an image or the images of an album.
	 * @var array
	 */
	var $fitbounds = NULL;

	/**
	 * geodata array(lat,lng)
	 * Default created from an image or the images of an album.
	 * @var array
	 */
	var $center = NULL;

	/**
	 * Optional class name to attach to the map html
	 * @var string
	 */
	var $class = '';

	/**
	 * "single" (one marker)
	 * "cluster" (several markers always clustered)
	 * "single-cluster" (markers of the images of the current album)
	 * Default created by the $geodata property: "single "if array with one entry, "cluster" if more entries
	 * @var string
	 */
	var $mode = NULL;

	/**
	 *
	 * Default false if set to true on single image maps the markers of all other images are shown as well.
	 * The current image's position will be highlighted.
	 * @var bool
	 */
	var $showalbummarkers = false;

	/**
	 * geodata array(lat,lng)
	 * Default created from the image marker or from the markers of the images of an album if in context
	 * @var array
	 */
	var $mapcenter = NULL;

	/**
	 * Unique number if using more than one map on a page
	 * @var int
	 */
	var $mapnumber = '';

	/**
	 * Default 100% for responsive map. Values like "100%", "100px" or "100em"
	 * Default taken from plugin options
	 * @var string
	 */
	var $width = NULL;

	/**
	 * Values like "100px" or "100em"
	 * Default taken from plugin options
	 * @var string
	 */
	var $height = NULL;

	/**
	 * Default zoom state
	 * Default taken from plugin options
	 * @var int
	 */
	var $zoom = NULL;
	var $minzoom = NULL;
	var $maxzoom = NULL;
	var $defaultlayer = NULL;
	var $layerslist = NULL;
	var $layer = NULL;

	/**
	 * Radius when clusters should be created on more than one marker
	 * Default taken from plugin options
	 * @var int
	 */
	var $clusterradius = NULL;

	/**
	 * Only if on an album page and if $imagepopups are enabled.
	 * If the imagepopus should contain thumbs of the images
	 * Default taken from plugin options
	 * @var bool
	 */
	var $markerpopup_title = false;
	var $markerpopup_desc = false;
	var $markerpopup_thumb = false;
	var $showmarkers = true;

	/**
	 * Mini map parameters
	 * @var string
	 */
	var $showminimap = false;
	var $minimap_width = NULL;
	var $minimap_height = NULL;
	var $minimap_zoom = NULL;

	/**
	 * Position of the map controls: "topleft", "topright", "bottomleft", "bottomright"
	 * Default taken from plugin options
	 * @var string
	 */
	var $zoomcontrolpos = NULL;
	var $showscale = NULL;
	var $showcursorpos = NULL;

	/**
	 * The current image or album object if not passing custom geodata
	 * @var object
	 */
	var $obj = NULL;

	/**
	 * the prefix text for the map css ID
	 */
	var $mapid = 'osm_map';

	/**
	 * show or hide the map
	 */
	var $hide = NULL;

	/**
	 * Text to display in the show/hide link
	 */
	var $label = NULL;

	/**
	 * The predefined array of all free map tile providers for Open Street Map
	 * array index is the provider, value is the option
	 * @var array
	 */
	static $tileProviders = array(
			'OpenStreetMap.Mapnik' => 'osmap_openstreetmap_mapnik',
			'OpenStreetMap.BlackAndWhite' => 'osmap_openstreetmap_blackandwhite',
			'OpenStreetMap.DE' => 'osmap_openstreetmap_de',
			'OpenStreetMap.France' => 'osmap_openstreetmap_france',
			'OpenStreetMap.HOT' => 'osmap_openstreetmap_hot',
			'OpenTopoMap' => 'osmap_opentopomap',
			'Thunderforest.OpenCycleMap' => 'osmap_thunderforest_opencyclemap',
			'Thunderforest.TransportDark' => 'osmap_thunderforest_transportdark',
			'Thunderforest.SpinalMap' => 'osmap_thunderforest_spinalmap',
			'Thunderforest.Landscape' => 'osmap_thunderforest_landscape',
			'Hydda.Full' => 'osmap_hydda_full',
			'MapBox.streets' => 'osmap_mapbox_streets',
			'MapBox.light' => 'osmap_mapbox_light',
			'MapBox.dark' => 'osmap_mapbox_dark',
			'MapBox.satellite' => 'osmap_mapbox_satellite',
			'MapBox.streets-satellite' => 'osmap_mapbox_streets-satellite',
			'MapBox.wheatpaste' => 'osmap_mapbox_wheatpaste',
			'MapBox.streets-basic' => 'osmap_mapbox_streets-basic',
			'MapBox.comic' => 'osmap_mapbox_comic',
			'MapBox.outdoors' => 'osmap_mapbox_outdoors',
			'MapBox.run-bike-hike' => 'osmap_mapbox_run-bike-hike',
			'MapBox.pencil' => 'osmap_mapbox_pencil',
			'MapBox.pirates' => 'osmap_mapbox_pirates',
			'MapBox.emerald' => 'osmap_mapbox_emerald',
			'MapBox.high-contrast' => 'osmap_mapbox_high-contrast',
			'Stamen.Watercolor' => 'osmap_stamen_watercolor',
			'Stamen.Terrain' => 'osmap_stamen_terrain',
			'Stamen.TerrainBackground' => 'osmap_stamen_terrainbackground',
			'Stamen.TopOSMRelief' => 'osmap_stamen_toposmrelief',
			'Stamen.TopOSMFeatures' => 'osmap_stamen_toposmfeatures',
			'Esri.WorldStreetMap' => 'osmap_esri_worldstreetmap',
			'Esri.DeLorme' => 'osmap_esri_delorme',
			'Esri.WorldTopoMap' => 'osmap_esri_worldtopomap',
			'Esri.WorldImagery' => 'osmap_esri_worldimagery',
			'Esri.WorldTerrain' => 'osmap_esri_worldterrain',
			'Esri.WorldShadedRelief' => 'osmap_esri_worldshadedrelief',
			'Esri.WorldPhysical' => 'osmap_esri_worldphysical',
			'Esri.OceanBasemap' => 'osmap_esri_oceanbasemap',
			'Esri.NatGeoWorldMap' => 'osmap_esri_natgeoworldmap',
			'Esri.WorldGrayCanvas' => 'osmap_esri_worldgraycanvas',
			'HERE.normalDay' => 'osmap_here_normalday',
			'HERE.normalDayCustom' => 'osmap_here_normaldaycustom',
			'HERE.normalDayGrey' => 'osmap_here_normaldaygrey',
			'HERE.normalDayMobile' => 'osmap_here_normaldaymobile',
			'HERE.normalDayGreyMobile' => 'osmap_here_normaldaygreymobile',
			'HERE.normalDayTransit' => 'osmap_here_normaldaytransit',
			'HERE.normalDayTransitMobile' => 'osmap_here_normaldaytransitmobile',
			'HERE.normalNight' => 'osmap_here_normalnight',
			'HERE.normalNightMobile' => 'osmap_here_normalnightmobile',
			'HERE.normalNightGrey' => 'osmap_here_normalnightgrey',
			'HERE.normalNightGreyMobile' => 'osmap_here_normalnightgreymobile',
			'HERE.basicMap' => 'osmap_here_basicmap',
			'HERE.mapLabels' => 'osmap_here_maplabels',
			'HERE.trafficFlow' => 'osmap_here_trafficflow',
			'HERE.carnavDayGrey' => 'osmap_here_carnavdaygrey',
			'HERE.hybridDay' => 'osmap_here_hybridday',
			'HERE.hybridDayMobile' => 'osmap_here_hybriddaymobile',
			'HERE.pedestrianDay' => 'osmap_here_pedestrianday',
			'HERE.pedestrianNight' => 'osmap_here_pedestriannight',
			'HERE.satelliteDay' => 'osmap_here_satelliteday',
			'HERE.terrainDay' => 'osmap_here_terrainday',
			'HERE.terrainDayMobile' => 'osmap_here_terraindaymobile',
			'FreeMapSK' => 'osmap_freemapsk',
			'MtbMap' => 'osmap_mtbmap',
			'CartoDB.Positron' => 'osmap_cartodb_positron',
			'CartoDB.PositronNoLabels' => 'osmap_cartodb_positronnolabels',
			'CartoDB.PositronOnlyLabels' => 'osmap_cartodb_positrononlylabels',
			'CartoDB.DarkMatter' => 'osmap_cartodb_darkmatter',
			'CartoDB.DarkMatterNoLabels' => 'osmap_cartodb_darkmatternolabels',
			'CartoDB.DarkMatterOnlyLabels' => 'osmap_cartodb_darkmatteronlylabels',
			'HikeBike.HikeBike' => 'osmap_hikebike_hikebike',
			'HikeBike.HillShading' => 'osmap_hikebike_hillshading',
			'BasemapAT.basemap' => 'osmap_basemapat_basemap',
			'BasemapAT.grau' => 'osmap_basemapat_grau',
			'BasemapAT.highdpi' => 'osmap_basemapat_highdpi',
			'BasemapAT.orthofoto' => 'osmap_basemapat_orthofoto',
			'NLS' => 'osmap_nls',
			'GeoportailFrance.ignMaps' => 'osmap_geoportailfrance_ignmaps',
			'GeoportailFrance.orthos' => 'osmap_geoportailfrance_orthos'
	);

	/**
	 * If no $geodata array is passed the function gets geodata from the current image or the images of the current album
	 * if in appropiate context.
	 *
	 * Alternatively you can pass an image or album object directly. This ignores the $geodata parameter then.
	 *
	 * The $geodata array requires this structure:
	 * Single marker:
	 *
	 * array(
	 *   array(
	 *      'lat' => <latitude>,
	 *      'long' => <longitude>,
	 *      'title' => 'some title',
	 *      'desc' => 'some description',
	 *      'thumb' => 'some html' // an <img src=""> call or else.
	 *   )
	 * );
	 *
	 * If you use html for title, desc or thumb be sure to use double quotes for attributes to avoid JS conflicts.
	 * For several markers add more arrays to the array.
	 *
	 * If you neither pass $geodata, an object or there is no current image/album you can still display a map.
	 * But in this case you need to set the $center and $fitbounds properties manually before printing a map.
	 *
	 * @global string $_gallery_page
	 * @param array $geodata Array as noted above if no current image or album should be used
	 * @param obj Image or album object If set this object is used and $geodatat is ignored if set as well
	 */
	function __construct($geodata = NULL, $obj = NULL) {
		global $_gallery_page, $_current_album, $_current_image;

		$this->showalbummarkers = getOption('osmap_showalbummarkers');
		if (is_object($obj)) {
			$this->obj = $obj;
			if (isImageClass($obj)) {
				$this->mode = 'single';
			} else if (isAlbumClass($obj)) {
				$this->mode = 'cluster';
			}
		} else {
			if (is_array($geodata)) {
				if (count($geodata) < 1) {
					$this->mode = 'single';
				} else {
					$this->mode = 'cluster';
				}
				$this->geodata = $geodata;
			} else {
				switch ($_gallery_page) {
					case 'image.php':
						if ($this->showalbummarkers) {
							$this->obj = $_current_album;
							$this->mode = 'single-cluster';
						} else {
							$this->obj = $_current_image;
							$this->mode = 'single';
						}
						break;
					case 'album.php':
					case 'favorites.php':
						$this->obj = $_current_album;
					case 'search.php':
						$this->mode = 'cluster';
						break;
				}
			}
		}
		$this->center = $this->getCenter();
		$this->fitbounds = $this->getFitBounds();
		$this->geodata = $this->getGeoData();
		$this->width = getOption('osmap_width');
		$this->height = getOption('osmap_height');
		$this->zoom = getOption('osmap_zoom');
		$this->minzoom = getOption('osmap_minzoom');
		$this->maxzoom = getOption('osmap_maxzoom');
		$this->zoomcontrolpos = getOption('osmap_zoomcontrolpos');
		$this->defaultlayer = $this->setMapTiles(getOption('osmap_defaultlayer'));
		$this->clusterradius = getOption('osmap_clusterradius');
		$this->cluster_showcoverage_on_hover = getOption('osmap_cluster_showcoverage_on_hover');
		$this->markerpopup_title = getOption('osmap_markerpopup_title');
		$this->markerpopup_desc = getOption('osmap_markerpopup_desc');
		$this->markerpopup_thumb = getOption('osmap_markerpopup_thumb');
		$this->showlayerscontrol = getOption('osmap_showlayerscontrol');
		// generate an array of selected layers
		$layerslist = self::$tileProviders;
		$selectedlayerslist = array();
		foreach ($layerslist as $layer => $layer_dbname) {
			if (getOption($layer_dbname)) {
				$selectedlayerslist[$layer] = $layer;
			}
		}
		// remove default Layer from layers list
		unset($selectedlayerslist[$this->defaultlayer]);
		$this->layerslist = $selectedlayerslist;
		$this->layerscontrolpos = getOption('osmap_layerscontrolpos');
		$this->showscale = getOption('osmap_showscale');
		$this->showcursorpos = getOption('osmap_showcursorpos');
		$this->showminimap = getOption('osmap_showminimap');
		$this->minimap_width = getOption('osmap_minimap_width');
		$this->minimap_height = getOption('osmap_minimap_height');
		$this->minimap_zoom = getOption('osmap_minimap_zoom');
		$this->hide = getOption('osmap_display');
	}

	/**
	 * Assigns the needed JS and CSS
	 */
	static function scripts() {
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/leaflet.css');
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/MarkerCluster.css');
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/MarkerCluster.Default.css');
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/openstreetmap.css');

		if (getOption('osmap_showcursorpos')) {
			scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/L.Control.MousePosition.css');
		}
		if (getOption('osmap_showminimap')) {
			scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/Control.MiniMap.min.css');
		}
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/leaflet.js');
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/leaflet.markercluster.js');
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/leaflet-providers.js');

		if (getOption('osmap_showcursorpos')) {
			scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/L.Control.MousePosition.js');
		}
		if (getOption('osmap_showminimap')) {
			scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/openstreetmap/Control.MiniMap.min.js');
		}
	}

	/**
	 * converts a cordinate in string format to a float
	 * NOTE: this function presumes that there are no thousands separators!!!
	 *
	 * @param string $num
	 * @return float
	 */
	static function inputConvert($num) {
		if (is_string($num)) {
			$d = preg_split('/[,\.]/', $num . '.0');
			$float = abs($d[0]) + $d[1] * pow(10, -strlen($d[1]));
			if (strpos($num, '-') !== FALSE) {
				$float = - $float;
			}
		} else {
			$float = (float) $num;
		}
		return $float;
	}

	/**
	 * $returns coordinate informations for an image
	 * @param $image		image object
	 */
	static function getGeoCoord($image) {
		if (isImageClass($image)) {
			$lat = $image->get('GPSLatitude');
			$long = $image->get('GPSLongitude');
			if (!empty($lat) && !empty($long)) {
				$lat_f = self::inputConvert($lat);
				$long_f = self::inputConvert($long);
				$thumb = "<a href='" . $image->getLink() . "'><img src='" . $image->getCustomImage(150, NULL, NULL, NULL, NULL, NULL, NULL, true) . "' alt='' /></a>";
				$title = shortenContent($image->getTitle(), 50, '...') . '<br />';
				$desc = shortenContent($image->getDesc(), 100, '...');
				return array('lat' => $lat_f, 'long' => $long_f, 'title' => $title, 'desc' => $desc, 'thumb' => $thumb, 'current' => 0);
			}
		}
		return false;
	}

	/**
	 * $returns coordinate informations for an image
	 * Adapted from the offical Zenphoto GoogleMap plugin by Stephen Billard (sbillard) & Vincent Bourganel (vincent3569)
	 * @param $image	image object
	 */
	function getImageGeodata($image) {
		global $_current_image;
		$result = self::getGeoCoord($image);
		if ($result) {
			if ($this->mode == 'single-cluster' && isset($_current_image) && ($image->filename == $_current_image->filename && $image->getAlbumname() == $_current_image->getAlbumname())) {
				$result['current'] = 1;
			}
		}
		return $result;
	}

	/**
	 * Gathers the map data for an album
	 * Adapted from the offical GoogleMap plugin by Stephen Billard (sbillard) & Vincent Bourganel (vincent3569)
	 * @param $album		album object
	 */
	function getAlbumGeodata($album) {
		$result = array();
		$images = $album->getImages(0, 0, null, null, false);
		foreach ($images as $an_image) {
			$image = newImage($album, $an_image);
			$imggeodata = $this->getImageGeodata($image);
			if (!empty($imggeodata)) {
				$result[] = $imggeodata;
			}
		}
		return $result;
	}

	/**
	 * Extracts the geodata from an image or the images of an album
	 * and creates the JS arrays for leaflet including title, description and thumb if set.
	 * @return array
	 */
	function getGeoData() {
		global $_current_image, $_current_album;
		$geodata = array();
		if (!is_null($this->geodata)) {
			return $this->geodata;
		}
		switch ($this->mode) {
			case 'single':
				$imggeodata = $this->getImageGeodata($this->obj);
				if (!empty($imggeodata)) {
					$geodata = array($imggeodata);
				}
				break;
			case 'single-cluster':
			case 'cluster':
				$albgeodata = $this->getAlbumGeodata($this->obj);
				if (!empty($albgeodata)) {
					$geodata = $albgeodata;
				}
				break;
		}
		if (empty($geodata)) {
			return NULL;
		} else {
			return $this->geodata = $geodata;
		}
	}

	/**
	 * Processes the geodata returned by getGeoData() and formats it to a string
	 * presenting a multidimensional Javascript array for use with leafletjs
	 * @return string
	 */
	function getGeoDataJS() {
		if (!is_null($this->geodatajs)) {
			return $this->geodatajs;
		}
		$geodata = $this->getGeoData();
		if (!empty($geodata)) {
			$count = -1;
			$js_geodata = '';
			foreach ($geodata as $geo) {
				$count++;
				$js_geodata .= ' geodata[' . $count . '] = {
                  lat : "' . number_format($geo['lat'], 12, '.', '') . '",
                  long : "' . number_format($geo['long'], 12, '.', '') . '",
                  title : "' . js_encode(shortenContent($geo['title'], 50, '...')) . '",
                  desc : "' . js_encode(shortenContent($geo['desc'], 100, '...')) . '",
                  thumb : "' . $geo['thumb'] . '",
                  current : "' . $geo['current'] . '"
                };';
			}
			return $this->geodatajs = $js_geodata;
		}
	}

	/**
	 * Returns the bounds the map should fit based on the geodata of an image or images of an album
	 * @return array
	 */
	function getFitBounds() {
		if (!is_null($this->fitbounds)) {
			return $this->fitbounds;
		}
		$geodata = $this->getGeoData();
		if (!empty($geodata)) {
			$geocount = count($geodata);
			$bounds = '';
			$count = '';
			foreach ($geodata as $g) {
				$count++;
				$bounds .= '[' . number_format($g['lat'], 12, '.', '') . ',' . number_format($g['long'], 12, '.', '') . ']';
				if ($count < $geocount) {
					$bounds .= ',';
				}
			}
			$this->fitbounds = $bounds;
		}
		return $this->fitbounds;
	}

	/**
	 * Returns the center point of the map. On an single image it is the marker of the image itself.
	 * On images from an album it is calculated from their geodata
	 * @return array
	 */
	function getCenter() {
		//$this->center = array(53.18, 10.38); //demotest
		if (!is_null($this->center)) {
			return $this->center;
		}
		$geodata = $this->getGeoData();
		if (!empty($geodata)) {
			switch ($this->mode) {
				case 'single':
					$this->center = array($geodata[0]['lat'], $geodata[0]['long']);
					break;
				case 'single-cluster':
					foreach ($geodata as $geo) {
						if ($geo['current'] == 1) {
							$this->center = array($geo['lat'], $geo['long']);
							break;
						}
					}
					break;
				case 'cluster':
					$_x = $_y = $_z = 0;
					$_n = count($geodata);
					foreach ($geodata as $coord) {
						$lat_f = $coord['lat'] * M_PI / 180;
						$long_f = $coord['long'] * M_PI / 180;
						$_x = $_x + cos($lat_f) * cos($long_f);
						$_y = $_y + cos($lat_f) * sin($long_f);
						$_z = $_z + sin($lat_f);
					}
					$_x = $_x / $_n;
					$_y = $_y / $_n;
					$_z = $_z / $_n;
					$lon = atan2($_y, $_x) * 180 / M_PI;
					$hyp = sqrt($_x * $_x + $_y * $_y);
					$lat = atan2($_z, $hyp) * 180 / M_PI;
					$this->center = array($lat, $lon);
					break;
			}
		}
		return $this->center;
	}

	/**
	 * Return the map tile js definition for leaflet and its leaflet-providers plugin.
	 * For certain map providers it include the access credentials.
	 *
	 * @return string
	 */
	function getTileLayerJS() {
		$maptile = explode('.', $this->layer);
		switch ($maptile[0]) {
			case 'MapBox':
				// should be Mapbox but follow leaflet-providers behavior
				return "L.tileLayer.provider('" . $maptile[0] . "', {"
								. "id: '" . strtolower($this->layer) . "', "
								. "accessToken: '" . getOption('osmap_mapbox_accesstoken') . "'"
								. "})";
			case 'HERE':
				return "L.tileLayer.provider('" . $this->layer . "', {"
								. "app_id: '" . getOption('osmap_here_appid') . "', "
								. "app_code: '" . getOption('osmap_here_appcode') . "'"
								. "})";
			case 'Thunderforest':
				return "L.tileLayer.provider('" . $this->layer . "', {"
								. "apikey: '" . getOption('osmap_thunderforest_apikey') . "'"
								. "})";
			case 'GeoportailFrance':
				return "L.tileLayer.provider('" . $this->layer . "', {"
								. "apikey: '" . getOption('osmap_geoportailfrance_apikey') . "'"
								. "})";
			default:
				return "L.tileLayer.provider('" . $this->layer . "')";
		}
	}

	/**
	 * Prints the required HTML and JS for the map
	 */
	function printMap() {
		$geodataJS = $this->getGeoDataJS();
		if (!empty($geodataJS)) {
			$class = $this->class;
			$id = $this->mapid . $this->mapnumber;
			$id_data = $id . '_data';
			$id_toggle = $id . '_toggle';
			if ($this->hide != 'show') {
				if (is_null($this->label)) {
					$this->label = gettext('OpenStreetMap Map');
				}
			}
			?>
			<div id="<?php echo $this->mapid . $this->mapnumber; ?>">
				<?php
				if ($this->hide == 'hide') {
					$class = $class . ' hidden_map';
					?>
					<script type="text/javascript">
						function toggle_<?php echo $id_data; ?>() {
							if ($('#<?php echo $id_data; ?>').hasClass('hidden_map')) {
								$('#<?php echo $id_data; ?>').removeClass('hidden_map');
								map.invalidateSize();
							} else {
								$('#<?php echo $id_data; ?>').addClass('hidden_map');
							}
						}
					</script>
					<span class="map_ref">
						<a id="<?php echo $id_toggle; ?>" href="javascript:toggle_<?php echo $id_data; ?>();" title="<?php echo gettext('Display or hide the Google Map.'); ?>"><?php echo $this->label; ?></a>
					</span>
					<?php
				} else if ($this->hide == 'colorbox') {
					?>
					<script type="text/javascript">
						window.addEventListener('load', function () {
							$('.google_map').colorbox({
								inline: true,
								innerWidth: $(window).width() * 0.8,
								href: "#<?php echo $id_data ?>",
								close: '<?php echo gettext("close"); ?>',
								onComplete: function () {
									map.invalidateSize(false);
								}
							});
						}, false);
					</script>
					<span class="map_ref">
						<a href="#" title="<?php echo $this->label; ?>" class="google_map"><?php echo $this->label; ?></a>
					</span>

					<?php
				}
				if ($class) {
					$class = ' class="' . $class . '"';
				}
				?>
				<style>
					.hidden_map {
						display: none;
					}
				</style>
				<?php
				if ($this->hide == 'colorbox') {
					?>
					<div class="colorboxmap hidden_map">
						<?php
					}
					?>
					<div id="<?php echo $id_data ?>"<?php echo $class; ?> style="width:<?php echo $this->width; ?>; height:<?php echo $this->height; ?>;"></div>
					<?php
					if ($this->hide == 'colorbox') {
						?>
					</div>
					<?php
				}
				?>
			</div>
			<script>


				var geodata = new Array();
			<?php echo $geodataJS; ?>
				var map = L.map('<?php echo $this->mapid . $this->mapnumber; ?>_data', {
					center: [<?php echo number_format($this->center[0], 12, '.', ''); ?>,<?php echo number_format($this->center[1], 12, '.', ''); ?>],
					zoom: <?php echo $this->zoom; ?>, //option
					zoomControl: false, // disable so we can position it below
					minZoom: <?php echo $this->minzoom; ?>,
			<?php if (!empty($this->maxzoom)) { ?>
						maxZoom: <?php echo $this->maxzoom; ?>
			<?php } ?>
				});
			<?php
			if (!$this->showlayerscontrol) {
				$this->layer = $this->defaultlayer;
				echo $this->getTileLayerJS() . '.addTo(map);';
			} else {
				$defaultlayer = $this->defaultlayer;
				$layerslist = $this->layerslist;
				$layerslist[$defaultlayer] = $defaultlayer;
				ksort($layerslist); // order layers list including default layer
				$baselayers = "";
				foreach ($layerslist as $layer) {
					if ($layer == $defaultlayer) {
						$baselayers = $baselayers . "'" . $defaultlayer . "': defaultLayer,\n";
					} else {
						$this->layer = $layer;
						$baselayers = $baselayers . "'" . $layer . "': " . $this->getTileLayerJS() . ",\n";
					}
				}
				$this->layer = $this->defaultlayer;
				?>
					var defaultLayer = <?php echo $this->getTileLayerJS(); ?>.addTo(map);
					var baseLayers = {
				<?php echo $baselayers; ?>
					};
					L.control.layers(baseLayers, null, {position: '<?php echo $this->layerscontrolpos; ?>'}).addTo(map);
				<?php
			}
			if ($this->mode == 'cluster' && $this->fitbounds) {
				?>
					map.fitBounds([<?php echo $this->fitbounds; ?>]);
				<?php
			}
			if ($this->showminimap) {
				?>
					var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
					var osm2 = new L.TileLayer(osmUrl);
					var miniMap = new L.Control.MiniMap(osm2, {
						toggleDisplay: true,
						zoomLevelOffset: <?php echo $this->minimap_zoom; ?>,
						width: <?php echo $this->minimap_width; ?>,
						height: <?php echo $this->minimap_height; ?>
					}).addTo(map);
				<?php
			}
			if ($this->showscale) {
				?>
					L.control.scale().addTo(map);
			<?php } ?>

				L.control.zoom({position: '<?php echo $this->zoomcontrolpos; ?>'}).addTo(map);
			<?php if ($this->showcursorpos) { ?>
					L.control.mousePosition().addTo(map);
				<?php
			}
			if ($this->showmarkers) {
				switch ($this->mode) {
					case 'single':
						?>
							var marker = L.marker([<?php echo number_format($this->geodata[0]['lat'], 12, '.', ''); ?>, <?php echo number_format($this->geodata[0]['long'], 12, '.', ''); ?>]).addTo(map); // from image
						<?php
						break;
					case 'single-cluster':
					case 'cluster':
						?>
							var markers_cluster = new L.MarkerClusterGroup({
								maxClusterRadius: <?php echo $this->clusterradius; ?>,
								showCoverageOnHover: <?php echo $this->cluster_showcoverage_on_hover; ?>
							}); //radius > Option
							$.each(geodata, function (index, value) {
								var text = '';
						<?php if ($this->markerpopup_title) { ?>
									text = value.title;
						<?php } ?>
						<?php if ($this->markerpopup_thumb) { ?>
									text += value.thumb;
						<?php } ?>
						<?php if ($this->markerpopup_desc) { ?>
									text += value.desc;
						<?php } ?>
								if (text === '') {
									markers_cluster.addLayer(L.marker([value.lat, value.long]));
								} else {
									markers_cluster.addLayer(L.marker([value.lat, value.long]).bindPopup(text));
								}
							});
							map.addLayer(markers_cluster);
						<?php
						break;
				}
			}
			?>
			</script>
			<?php
		}
	}

	/**
	 * It returns the provider chosen if it is valid or the default 'OpenStreetMap.Mapnik' tile
	 *
	 * @param string $tileprovider The tile provider to validate
	 * @return string
	 */
	function setMapTiles($tileprovider = null) {
		if (isset(self::$tileProviders[$tileprovider])) {
			return $tileprovider;
		} else {
			return 'OpenStreetMap.Mapnik';
		}
	}

// osm class end
}

/**
 * Template function wrapper for the openStreetMap class to show a map with geodata markers
 * for the current image or collected the images of an album.
 *
 * For more flexibility use the class directly.
 *
 * The map is not shown if there is no geodata available.
 *
 * @global obj $_current_album
 * @global obj $_current_image
 * @global string $_gallery_page
 * @param array $geodata Array of the geodata to create and display markers. See the constructor of the openStreetMap Class for the require structure
 * @param string $width Width with unit, e.g. 100%, 100px, 100em
 * @param string $height Height with unit, e.g. 100px, 100em
 * @param array $mapcenter geodata array(lat,lng);
 * @param int $zoom Number of the zoom 0 -
 * @param array $fitbounds geodata array('min' => array(lat,lng), 'max => array(lat,lng))
 * @param string $class Class name to attach to the map element
 * @param int $mapnumber If calling more than one map per page an unique number is required
 * @param obj $obj Image or album object to skip current image or album and also $geodata
 * @param bool $minimap True to show the minimap in the lower right corner
 * @param string $id the CSS id for the map. NOTE: the map number will be appended to this string!
 * @param string $hide the initial display state for the map. Not yet implemented
 */
function printOpenStreetMap($geodata = NULL, $width = NULL, $height = NULL, $mapcenter = NULL, $zoom = NULL, $fitbounds = NULL, $class = '', $mapnumber = NULL, $obj = NULL, $minimap = false, $id = NULL, $hide = NULL, $text = NULL) {

	$map = new openStreetMap($geodata, $obj);
	if (!is_null($width)) {
		$map->width = $width;
	}
	if (!is_null($height)) {
		$map->height = $height;
	}
	if (!is_null($mapcenter)) {
		$map->center = $mapcenter;
	}
	if (!is_null($zoom)) {
		$map->zoom = $zoom;
	}
	if (!is_null($fitbounds)) {
		$map->fitbounds = $fitbounds;
	}
	if (!is_null($class)) {
		$map->class = $class;
	}
	if (!is_null($mapnumber)) {
		$map->mapnumber = $mapnumber;
	}
	if ($minimap) {
		$map->showminimap = true;
	}
	if ($id) {
		$map->mapid = $id;
	}
	if ($hide) {
		$map->hide = $hide;
	}
	if (!is_null($text)) {
		$map->label = $text;
	}

	$map->printMap();
}
