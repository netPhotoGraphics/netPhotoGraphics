<?php

/**
 *
 * Use this plugin to handle filetypes as "images" that are not otherwise provided for by other plugins.
 *
 * This plugin provides handlers for <b>TIFF</b> and <b>PDF</b> files. These scripts relegate the rendering of the item to
 * browser native support, so may not work on a particular browser. However; they provide a starting point for custom file
 * handling scripts.
 *
 * Default thumbnail images should be created in the <var>%USER_PLUGIN_FOLDER%/class-AnyFile</var> folder. The naming convention is
 * <i>suffix</i><var>Default.png</var>.
 *
 * The default behavior of the content display is to display an image based on the above default thumbnail. You can
 * extend this by creating php script in the <var>%USER_PLUGIN_FOLDER%/class-AnyFile</var> folder. This script is named
 * <var>class-Suffix.php</var> where suffix is the upper case first file suffix that is being handled. The script defines
 * an object named <var>Suffix</var> which extends <var>AnyFile</var> and has at least the <var>getContents()</var>
 * method.
 *
 * File suffixes supported by the plugin are computed from the list of thumbnail and/or class scripts.
 *
 *
 * The plugin is an extension of <var>TextObject_core</var>. For more details see the <i>class-textobject</i> plugin.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/class-AnyFile
 * @pluginCategory media
 *
 */
$plugin_is_filter = 990 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Provides a means for handling arbitrary file types as "images".');
}

$option_interface = 'AnyFile';

require_once(__DIR__ . '/class-textobject/class-textobject_core.php');

class AnyFile extends TextObject_core {

	/**
	 * creates a WEBdocs (image standin)
	 *
	 * @param object $album the owner album
	 * @param string $filename the filename of the text file
	 * @return TextObject
	 */
	function __construct($album = NULL, $filename = NULL, $quiet = false) {

		if (OFFSET_PATH == 2) {
			$supported = getOption('AnyFileSuffixList');
			if ($supported) {
				$supported = getSerializedArray($supported);
				if (!is_dir(USER_PLUGIN_SERVERPATH . 'class-AnyFile')) {
					mkdir(USER_PLUGIN_SERVERPATH . 'class-AnyFile');
				}
				foreach ($supported as $suffix) {
					if ($suffix && !file_exists(USER_PLUGIN_SERVERPATH . 'class-AnyFile/' . $suffix . 'Default.png')) {
						copy(PLUGIN_SERVERPATH . 'class-AnyFile/Default.png', USER_PLUGIN_SERVERPATH . 'class-AnyFile/' . $suffix . 'Default.png');
					}
				}
				purgeOption('AnyFileSuffixList');
			}
			if (file_exists(USER_PLUGIN_SERVERPATH . 'class-AnyFile/Default.png')) {
				unlink(USER_PLUGIN_SERVERPATH . 'class-AnyFile/Default.png');
			}
			purgeOption('AnyFile_file_new');
			purgeOption('AnyFile_file_list');
			purgeOption('AnyFile_watermark');
		}

		$this->watermarkDefault = getOption('AnyFile_watermark_default_images');

		if (is_object($album)) {
			parent::__construct($album, $filename, $quiet);
		}
	}

	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(gettext('Watermark default images') => array('key' => 'AnyFile_watermark_default_images', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to place watermark image on default thumbnail images.'))
		);
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
	 * Returns the content of the text file
	 *
	 * @param int $w optional width
	 * @param int $h optional height
	 * @param dummy $container not used
	 * @return string
	 */
	function getContent($w = NULL, $h = NULL) {
		$this->updateDimensions();
		if (is_null($w))
			$w = $this->getWidth();
		if (is_null($h))
			$h = $this->getHeight();
		$s = min($w, $h);
		/*
		 * just return the thumbnail image as we do not know how to render the file.
		 */
		return '<img src="' . html_encode($this->getCustomImage(array('size' => $s, 'thumb' => 3))) . '" class="anyfile_default" width=' . $s . ' height=' . $s . '>';
	}

	static function init() {
		$supported = array();
		$files = getPluginFiles('class-AnyFile/*.*');
		unset($files['class-AnyFile/Default']);
		foreach ($files as $file) {
			switch (getSuffix($file)) {
				case 'php':
					$handler = strtolower(str_replace('class-', '', stripSuffix(basename($file))));
					break;
				case 'png':
					$handler = strtolower(str_replace('Default', '', stripSuffix(basename($file))));
					break;
			}
			if (isset($handler)) {
				$supported[] = $handler;
			}
			unset($handler);
		}

		$supported = array_unique($supported);
		foreach ($supported as $suffix) {
			$handler = getPlugin(ucfirst($suffix) . '.php');
			if ($handler) {
				require_once($handler);
				Gallery::addImageHandler($suffix, $handler);
			} else {
				Gallery::addImageHandler($suffix, 'AnyFile');
			}
		}
	}

}

AnyFile::init();
