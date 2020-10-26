<?php

/**
 *
 * Supports files of the following types:
 * <ol>
 * 	<li><var>.txt</var></var>
 * 	<li><var>.htm</var></li>
 * 	<li><var>.html</var></var>
 * <li><var>.pdf</var></var>
 * </ul>
 * 		The contents of these files are "dumpped" into a container based on your
 * 		theme	"image size" options. This has a class of "textobject" so it can be styled.
 *
 * What this plugin really is for is to serve as a model of how a plugin can be made to handle file types
 * that are not handle natively.
 *
 * Some key points to note:
 * <ul>
 * 	<li>The naming convention for these plugins is class-«handler class».php.</li>
 * 	<li>The statement setting the plugin_is_filter variable must be near the front of the file. This is important	as it is the indicator to the plugin loader to load the script at the same point that other	object modules are loaded.</li>
 * 	<li>These objects are extension to the "Image" class. This means they have all the properties of	an image plus whatever you add. Of course you will need to override some of the image class functions to implement the functionality of your new class.</li>
 * 	<li>There is one VERY IMPORTANT method that you must provide which is not part of the "Image" base class. The	getContent() method. This method is called by template-functions.php in place of where it would normally put a URL to the image to show. This method must do everything needed to cause your image object to be viewable by the  browser.</li>
 * </ul>
 *
 * So, briefly, the first lines of code below are the standard plugin interface to Admin.
 * Then there are calls on <var>addPlginType(«file extension», «Object Name»);</var> This function registers the plugin as the
 * handler for files with the specified extension. If the plugin can handle more than one file extension, make a call
 * to the registration function for each extension that it handles.
 *
 * Then the plugin loads the common non-image handler object
 *
 * The rest is the object class for handling these files.
 *
 * The code of the object instantiation function is mostly required. Plugin <i>images</i> follow the lead of <var>class-video</var> in that
 * if there is a real image file with the same name save the suffix, it will be considered the thumb image of the object.
 * This image is fetched by the call on <var>checkObjectsThumb()</var>. There is also code in the <var>getThumb()</var> method to deal with
 * this property.
 *
 * Since text files have no natural height and width, we set them based on the theme image size options.
 *
 * <var>getThumb()</var> is responsible for generating the thumbnail image for the object. As above, if there is a similar named real
 * image, it will be used. Otherwise [for this object implementation] we will use a thumbnail image provided with the plugin.
 * The particular form of the file name used when there is no thumb stand-in image allows choosing an image in the
 * plugin folder.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/class-textobject
 * @pluginCategory media
 *
 */
$plugin_is_filter = 800 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Provides a means for showing text type documents (.txt, .html, .htm).');
}

$option_interface = 'textObject';

Gallery::addImageHandler('htm', 'TextObject');
Gallery::addImageHandler('html', 'TextObject');
Gallery::addImageHandler('txt', 'TextObject');
Gallery::addImageHandler('pdf', 'TextObject');

require_once(__DIR__ . '/class-textobject/class-textobject_core.php');

class TextObject extends TextObject_core {

	function __construct($album = NULL, $filename = NULL, $quiet = false) {

		$this->watermark = getOption('TextObject_watermark');
		$this->watermarkDefault = getOption('textobject_watermark_default_images');

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
		return array(gettext('Watermark default images') => array('key' => 'textobject_watermark_default_images', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to place watermark image on default thumbnail images.')));
	}

}
