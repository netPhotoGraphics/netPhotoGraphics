<?php

/**
 * Image Class
 * @package classes
 */
// force UTF-8 Ø

define('WATERMARK_IMAGE', 1);
define('WATERMARK_THUMB', 2);
define('WATERMARK_FULL', 4);

/**
 * Returns a new "image" object based on the file extension
 *
 * @param object $album the owner album
 * @param string $filename the filename
 * @param bool $quiet set true to supress error messages (used by loadimage)
 * @return object
 */
function newImage($album, $filename = NULL, $quiet = false) {
	global $_missing_image;
	if (is_array($album)) {
		$xalbum = newAlbum($album['folder'], NULL, $quiet);
		$filename = $album['filename'];
		$dyn = false;
	} else if (is_array($filename)) {
		$xalbum = newAlbum($filename['folder'], NULL, $quiet);
		$filename = $filename['filename'];
		$dyn = is_object($album) && $album->isDynamic();
	} else if (is_object($album) && $album->isDynamic()) {
		$dyn = true;
		$album->getImages();
		$xalbum = array_keys($album->imageNames, $filename);
		$xalbum = reset($xalbum);
		$xalbum = newAlbum(dirname($xalbum), NULL, $quiet);
	} else {
		$xalbum = $album;
		$dyn = false;
	}

	if (!is_object($xalbum) || !$xalbum->exists || !isAlbumClass($xalbum)) {
		$msg = sprintf(gettext('Bad album object parameter to newImage(%s)'), $filename);
	} else {
		if ($object = Gallery::imageObjectClass($filename)) {
			$image = New $object($xalbum, $filename, $quiet);
			if ($album && is_subclass_of($album, 'AlbumBase') && $dyn) {
				$image->albumname = $album->name;
				$image->albumlink = $album->linkname;
				$image->albumnamealbum = $album;
			}
			npgFilters::apply('image_instantiate', $image);
			if ($image->exists) {
				return $image;
			}
			return $_missing_image;
		}
		$msg = sprintf(gettext('Bad filename suffix in newImage(%s)'), $filename);
	}
	if (!$quiet) {
		debugLogBacktrace($msg);
	}
	return $_missing_image;
}

/**
 * Returns true if the object is an 'image' object
 *
 * @param object $image
 * @return bool
 */
function isImageClass($image) {
	return is_object($image) && ($image->table == 'images');
}

/**
 * handles 'picture' images
 */
class Image extends MediaObject {

	public $filename; // true filename of the image.
	public $exists = true; // Does the image exist?
	public $webpath; // The full URL path to the original image.
	public $localpath; // Latin1 full SERVER path to the original image.
	public $displayname; // $filename with the extension stripped off.
	public $album; // An album object for the album containing this image.
	public $albumname; // The name of the album for which this image was instantiated. (MAY NOT be $this->album->name!!!!).
	public $albumnamealbum; //	An album object representing the above;
	public $albumlink; // "rewrite" verwion of the album name, eg. may not have the .alb
	public $imagefolder; // The album folder containing the image (May be different from the albumname!!!!)
	protected $index; // The index of the current image in the album array.
	protected $sortorder; // The position that this image should be shown in the album
	public $filemtime; // Last modified time of this image
	public $sidecars = array(); // keeps the list of suffixes associated with this image
	public $manage_rights = MANAGE_ALL_ALBUM_RIGHTS;
	public $manage_some_rights = ALBUM_RIGHTS;
	public $access_rights = ALL_ALBUMS_RIGHTS;
	// Plugin handler support
	public $objectsThumb = NULL; // Thumbnail image for the object

	/**
	 * Constructor for class-image
	 *
	 * Do not call this constructor directly unless you really know what you are doing!
	 * Use instead the function newImage() which will instantiate an object of the
	 * correct class for the file type.
	 *
	 * @param object &$album the owning album
	 * @param sting $filename the filename of the image
	 * @return Image
	 */
	function __construct($album, $filename, $quiet = false) {
		global $_current_admin_obj;
		// $album is an Album object; it should already be created.
		$msg = $this->invalid($album, $filename);
		if ($msg) {
			$this->exists = false;
			if (!$quiet) {
				debugLogBacktrace($msg);
			}
			return;
		}

		// This is where the magic happens...
		$this->album = $album;
		$album_name = $album->name;
		$new = $this->instantiate('images', array('filename' => $filename, 'albumid' => $this->album->getID()), 'filename', false, empty($album_name));
		$this->checkForPublish();
		if ($new || $this->filemtime != $this->get('mtime')) {
			if ($new) {
				$this->setTitle($this->displayname);
				if ($_current_admin_obj) {
					$this->setOwner($_current_admin_obj->getUser());
				}
				$this->setDefaultSortOrder();
				setOption('last_admin_action', time());
			}
			$this->updateMetaData(); // extract info from image
			$this->updateDimensions(); // deal with rotation issues
			$this->set('mtime', $this->filemtime);
			$this->set('filesize', filesize($this->localpath));
			$this->save();
			if ($new)
				npgFilters::apply('new_image', $this);
		}
	}

	/**
	 *
	 * "Magic" function to return a string identifying the object when it is treated as a string
	 * @return string
	 */
	public function __toString() {
		return $this->table . '(' . $this->imagefolder . '/' . $this->filename . ')';
	}

	/**
	 * propagate last change to parent
	 */
	function save() {
		$success = parent::save();
		if ($success == 1) {
			$this->album->set('lastchange', date('Y-m-d H:i:s'));
			$this->album->save();
		}
		return $success;
	}

	/**
	 * Common validity check function
	 *
	 * @param type $album
	 * @param type $filename
	 * @return string
	 */
	function invalid($album, $filename) {
		$msg = false;
		if (!is_object($album) || !$album->exists) {
			$msg = sprintf(gettext('Invalid %s instantiation: Album does not exist'), get_class($this)) . ' (' . $album . ')';
		} else if (!file_exists($album->localpath . $filename) || is_dir($album->localpath . $filename) || !$this->classSetup($album, $filename)) {
			$msg = sprintf(gettext('Invalid %s instantiation: file does not exist'), get_class($this)) . ' (' . $album . '/' . $filename . ')';
		}
		return $msg;
	}

	/**
	 * returns the database fields used by the object
	 * @return array
	 *
	 * @author Stephen Billard
	 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
	 */
	static function getMetadataFields() {
		$fields = array(
				// Database Field => array(
				// 0:source, 1:Metadata Key, 2:Display Text, 3:Display?	4:size,	5:enabled, 6:type, 7:linked
				// )
				'EXIFArtist' => array('IFD0', 'Artist', gettext('Artist'), false, 52, true, 'string', false),
				'EXIFContrast' => array('SubIFD', 'Contrast', gettext('Contrast Setting'), false, 52, true, 'string', false),
				'EXIFCopyright' => array('IFD0', 'Copyright', gettext('Copyright Holder'), false, 128, true, 'string', false),
				'EXIFDateTime' => array('IFD0', 'DateTime', gettext('Time Taken'), true, 52, true, 'datetime', false),
				'EXIFDateTimeDigitized' => array('SubIFD', 'DateTimeDigitized', gettext('Time Digitized'), true, 52, true, 'datetime', false),
				'EXIFDateTimeOriginal' => array('SubIFD', 'DateTimeOriginal', gettext('Original Time Taken'), true, 52, true, 'datetime', false),
				'EXIFDescription' => array('IFD0', 'ImageDescription', gettext('Image Title'), false, 52, true, 'string', false),
				'EXIFExposureBiasValue' => array('SubIFD', 'ExposureBiasValue', gettext('Exposure Compensation'), true, 52, true, 'string', false),
				'EXIFExposureTime' => array('SubIFD', 'ExposureTime', gettext('Exposure time'), true, 52, true, 'string', false),
				'EXIFFlash' => array('SubIFD', 'Flash', gettext('Flash Fired'), true, 52, true, 'string', false),
				'EXIFFNumber' => array('SubIFD', 'FNumber', gettext('Aperture'), true, 20, true, 'string', false),
				'EXIFFocalLength' => array('SubIFD', 'FocalLength', gettext('Focal Length'), true, 20, true, 'string', false),
				'EXIFFocalLengthIn35mmFilm' => array('SubIFD', 'FocalLengthIn35mmFilm', gettext('35mm Focal Length Equivalent'), false, 52, true, 'string', false),
				'EXIFGPSAltitude' => array('GPS', 'Altitude', gettext('Altitude'), false, 52, true, 'number', false),
				'EXIFGPSAltitudeRef' => array('GPS', 'Altitude Reference', gettext('Altitude Reference'), false, 52, true, 'string', 'EXIFGPSAltitude'),
				'EXIFGPSLatitude' => array('GPS', 'Latitude', gettext('Latitude'), false, 52, true, 'number', false),
				'EXIFGPSLatitudeRef' => array('GPS', 'Latitude Reference', gettext('Latitude Reference'), false, 52, true, 'string', 'EXIFGPSLatitude'),
				'EXIFGPSLongitude' => array('GPS', 'Longitude', gettext('Longitude'), false, 52, true, 'number', false),
				'EXIFGPSLongitudeRef' => array('GPS', 'Longitude Reference', gettext('Longitude Reference'), false, 52, true, 'string', 'EXIFGPSLongitude'),
				'EXIFImageHeight' => array('SubIFD', 'ExifImageHeight', gettext('Original Height'), false, 52, true, 'string', false),
				'EXIFImageWidth' => array('SubIFD', 'ExifImageLength', gettext('Original Length'), false, 52, true, 'string', false),
				'EXIFISOSpeedRatings' => array('SubIFD', 'ISOSpeedRatings', gettext('ISO Sensitivity'), true, 52, true, 'string', false),
				'EXIFLensInfo' => array('SubIFD', 'LensInfo', gettext('Lens Specification'), false, 52, true, 'string', false),
				'EXIFLensType' => array('SubIFD', 'LensType', gettext('Lens Model'), false, 52, true, 'string', false),
				'EXIFMake' => array('IFD0', 'Make', gettext('Camera Maker'), true, 52, true, 'string', false),
				'EXIFMeteringMode' => array('SubIFD', 'MeteringMode', gettext('Metering Mode'), true, 52, true, 'string', false),
				'EXIFModel' => array('IFD0', 'Model', gettext('Camera Model'), true, 52, true, 'string', false),
				'EXIFOrientation' => array('IFD0', 'Orientation', gettext('Orientation'), false, 52, true, 'string', false),
				'EXIFSaturation' => array('SubIFD', 'Saturation', gettext('Saturation Setting'), false, 52, true, 'string', false),
				'EXIFSharpness' => array('SubIFD', 'Sharpness', gettext('Sharpness Setting'), false, 52, true, 'string', false),
				'EXIFShutterSpeedValue' => array('SubIFD', 'ShutterSpeedValue', gettext('Shutter Speed'), true, 52, true, 'string', false),
				'EXIFSoftware' => array('IFD0', 'Software', gettext('Software'), false, 999, true, 'string', false),
				'EXIFSubjectDistance' => array('SubIFD', 'SubjectDistance', gettext('Subject Distance'), false, 52, true, 'string', false),
				'EXIFWhiteBalance' => array('SubIFD', 'WhiteBalance', gettext('White Balance'), false, 52, true, 'string', false),
				'IPTCByLine' => array('IPTC', 'ByLine', gettext('Byline'), false, 32, true, 'string', false),
				'IPTCByLineTitle' => array('IPTC', 'ByLineTitle', gettext('Byline Title'), false, 32, true, 'string', false),
				'IPTCCity' => array('IPTC', 'City', gettext('City'), false, 32, true, 'string', false),
				'IPTCContact' => array('IPTC', 'Contact', gettext('Contact'), false, 128, true, 'string', false),
				'IPTCContentLocationCode' => array('IPTC', 'ContentLocationCode', gettext('Content Location Code'), false, 3, true, 'string', false),
				'IPTCContentLocationName' => array('IPTC', 'ContentLocationName', gettext('Content Location Name'), false, 64, true, 'string', false),
				'IPTCCopyright' => array('IPTC', 'Copyright', gettext('Copyright Notice'), false, 128, true, 'string', false),
				'IPTCDateCreated' => array('IPTC', 'DateCreated', gettext('Date Created'), false, 8, true, 'date', false),
				'IPTCDigitizeDate' => array('IPTC', 'DigitizeDate', gettext('Digital Creation Date'), false, 8, true, 'date', false),
				'IPTCDigitizeTime' => array('IPTC', 'DigitizeTime', gettext('Digital Creation Time'), false, 11, true, 'time', false),
				'IPTCImageCaption' => array('IPTC', 'ImageCaption', gettext('Image Caption'), false, 2000, true, 'string', false),
				'IPTCImageCaptionWriter' => array('IPTC', 'ImageCaptionWriter', gettext('Image Caption Writer'), false, 32, true, 'string', false),
				'IPTCImageCredit' => array('IPTC', 'ImageCredit', gettext('Image Credit'), false, 32, true, 'string', false),
				'IPTCImageHeadline' => array('IPTC', 'ImageHeadline', gettext('Image Headline'), false, 256, true, 'string', false),
				'IPTCKeywords' => array('IPTC', 'Keywords', gettext('Keywords'), false, 0, true, 'string', false),
				'IPTCLocationCode' => array('IPTC', 'LocationCode', gettext('Country/Primary Location Code'), false, 3, true, 'string', false),
				'IPTCLocationName' => array('IPTC', 'LocationName', gettext('Country/Primary Location Name'), false, 64, true, 'string', false),
				'IPTCObjectName' => array('IPTC', 'ObjectName', gettext('Object Name'), false, 256, true, 'string', false),
				'IPTCOriginatingProgram' => array('IPTC', 'OriginatingProgram', gettext('Originating Program'), false, 32, true, 'string', false),
				'IPTCProgramVersion' => array('IPTC', 'ProgramVersion', gettext('Program Version'), false, 10, true, 'string', false),
				'IPTCSource' => array('IPTC', 'Source', gettext('Image Source'), false, 32, true, 'string', false),
				'IPTCState' => array('IPTC', 'State', gettext('Province/State'), false, 32, true, 'string', false),
				'IPTCSubLocation' => array('IPTC', 'SubLocation', gettext('Sub-location'), false, 32, true, 'string', false),
				'IPTCTimeCreated' => array('IPTC', 'TimeCreated', gettext('Time Created'), false, 11, true, 'time', false)
		);

		return $fields;
	}

	/**
	 * (non-PHPdoc)
	 * @see PersistentObject::setDefaults()
	 */
	protected function setDefaults() {
		global $_gallery;
		$this->set('mtime', $this->filemtime);
		$this->updateDimensions(); // deal with rotation issues
		$this->setShow($_gallery->getImagePublish());
	}

	/**
	 * generic "image" class setup code
	 * Returns true if valid image.
	 *
	 * @param object $album the images' album
	 * @param string $filename of the image
	 * @return bool
	 *
	 */
	protected function classSetup(&$album, $filename) {
		global $_current_admin_obj;

		if (TEST_RELEASE) {
			$bt = debug_backtrace();
			$good = false;
			foreach ($bt as $b) {
				if ($b['function'] == "newImage") {
					$good = true;
					break;
				}
			}
			if (!$good) {
				debugLogBacktrace(gettext('An image object was instantiated without using the newImage() function.'));
			}
		}

		$fileFS = internalToFilesystem($filename);
		if ($filename != filesystemToInternal($fileFS)) { // image name spoof attempt
			return false;
		}
		$this->albumnamealbum = $this->album = &$album;
		if ($album->name == '') {
			$this->webpath = ALBUM_FOLDER_WEBPATH . $filename;
			$this->localpath = ALBUM_FOLDER_SERVERPATH . internalToFilesystem($filename);
		} else {
			$this->webpath = ALBUM_FOLDER_WEBPATH . $album->name . "/" . $filename;
			$this->localpath = $album->localpath . $fileFS;
		}
		$this->imagefolder = $this->albumlink = $this->albumname = $album->name;
		$this->filename = $filename;
		$this->displayname = substr($this->filename, 0, strrpos($this->filename, '.'));
		if (empty($this->displayname)) {
			$this->displayname = $this->filename;
		}
		$this->comments = null;
		$this->filemtime = filemtime($this->localpath);
		$date = $this->get('date');
		if (!$date || $date == '0000-00-00 00:00:00') {
			$this->set('date', date('Y-m-d H:i:s', $this->filemtime));
		}
		return true;
	}

	/**
	 * Returns the image filename
	 *
	 * @return string
	 */
	function getFileName() {
		return $this->filename;
	}

	/**
	 * Returns true if the file has changed since last time we looked
	 *
	 * @return bool
	 */
	protected function fileChanged() {
		$storedmtime = $this->get('mtime');
		return (empty($storedmtime) || $this->filemtime > $storedmtime);
	}

	/**
	 * Returns an array of EXIF data
	 *
	 * @return array
	 */
	function getMetaData() {
		global $_exifvars;
		$exif = array();
		// Put together an array of EXIF data to return
		foreach ($_exifvars as $field => $exifvar) {
			//	only enabled image metadata
			if ($_exifvars[$field][METADATA_FIELD_ENABLED]) {
				$exif[$field] = $this->get($field);
			}
		}
		return $exif;
	}

	/**
	 * these functions return the "consolidated" geo coordinates as floats
	 * strange things happen with locales, so best to be "separator blind"
	 *
	 * @return float
	 */
	private function floatGPS($coord) {
		$d = preg_split('/[,\.]/', str_replace('-', '', $coord) . '.0');
		$v = floatval($d[0] + $d[1] * pow(10, -strlen($d[1])));
		if (substr($coord, 0, 1) == '-') {
			$v = -$v;
		}
		return $v;
	}

	function getGPSLatitude() {
		$coord = $this->get('GPSLatitude');
		if (!is_null($coord)) {
			return self::floatGPS($coord);
		}
		return NULL;
	}

	function getGPSLongitude() {
		$coord = $this->get('GPSLongitude');
		if (!is_null($coord)) {
			return self::floatGPS($coord);
		}
		return NULL;
	}

	/**
	 * check if a metadata field should be used
	 * @global type $_exifvars
	 * @param type $field
	 * @return type
	 */
	function fetchMetadata($field) {
		global $_exifvars;
		if ($_exifvars[$field][METADATA_FIELD_ENABLED]) {
			return $this->get($field);
		} else {
			return false;
		}
	}

	/**
	 * Parses Exif/IPTC data
	 *
	 */
	function updateMetaData() {
		require_once (CORE_SERVERPATH . 'lib-metadata.php');
		Metadata::update($this);
	}

	/**
	 * Update this object's values for width and height.
	 *
	 */
	function updateDimensions() {
		$discard = NULL;
		$size = gl_imageDims($this->localpath);
		if (is_array($size)) {
			$width = $size['width'];
			$height = $size['height'];
			if (gl_imageCanRotate()) {
				// Swap the width and height values if the image should be rotated
				if ($rotation = $this->get('rotation')) {
					$rotation = substr(trim($this->get('rotation'), '!'), 0, 1);
				}
				switch ($rotation) {
					case 5:
					case 6:
					case 7:
					case 8:
						$width = $size['height'];
						$height = $size['width'];
						break;
				}
			}
			$this->set('width', $width);
			$this->set('height', $height);
		}
	}

	/**
	 * Returns the width of the image
	 *
	 * @return int
	 */
	function getWidth() {
		$w = $this->get('width');
		if (empty($w)) {
			$this->updateDimensions();
			$this->save();
			$w = $this->get('width');
		}
		return $w;
	}

	/**
	 * Returns the height of the image
	 *
	 * @return int
	 */
	function getHeight() {
		$h = $this->get('height');
		if (empty($h)) {
			$this->updateDimensions();
			$this->save();
			$h = $this->get('height');
		}
		return $h;
	}

	function getRotation() {
		return $this->get('rotation');
	}

	/**
	 * Returns the side car files associated with the image
	 *
	 * @return array
	 */
	function getSidecars() {
		$files = safe_glob(stripSuffix($this->localpath) . '.*');
		$result = array();
		foreach ($files as $file) {
			if (!is_dir($file) && in_array(strtolower(getSuffix($file)), $this->sidecars)) {
				$result[basename($file)] = $file;
			}
		}
		return $result;
	}

	function addSidecar($car) {
		$this->sidecars[$car] = $car;
	}

	/**
	 * Returns the album that holds this image
	 *
	 * @return object
	 */
	function getAlbum() {
		return $this->album;
	}

	/**
	 * synonym for getAlbum so in can be used when we don't know if object is album or image
	 * @return OBJECT
	 */
	function getParent() {
		return $this->album;
	}

	/**
	 * Retuns the folder name of the album that holds this image
	 *
	 * @return string
	 */
	function getAlbumName() {
		return $this->albumname;
	}

	/**
	 * Returns the location field of the image
	 *
	 * @return string
	 */
	function getLocation($locale = NULL) {
		$text = $this->get('location');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Stores the location field of the image
	 *
	 * @param string $location text for the location
	 */
	function setLocation($place) {
		$this->set('location', $place);
	}

	/**
	 * Returns the city field of the image
	 *
	 * @return string
	 */
	function getCity($locale = NULL) {
		$text = $this->get('city');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Stores the city field of the image
	 *
	 * @param string $city text for the city
	 */
	function setCity($city) {
		$this->set('city', npgFunctions::tagURLs($city));
	}

	/**
	 * Returns the state field of the image
	 *
	 * @return string
	 */
	function getState($locale = NULL) {
		$text = $this->get('state');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Stores the state field of the image
	 *
	 * @param string $state text for the state
	 */
	function setState($state) {
		$this->set('state', npgFunctions::tagURLs($state));
	}

	/**
	 * Returns the country field of the image
	 *
	 * @return string
	 */
	function getCountry($locale = NULL) {
		$text = $this->get('country');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Stores the country field of the image
	 *
	 * @param string $country text for the country filed
	 */
	function setCountry($country) {
		$this->set('country', npgFunctions::tagURLs($country));
	}

	/**
	 * Returns the credit field of the image
	 *
	 * @return string
	 */
	function getCredit($locale = NULL) {
		$text = $this->get('credit');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Stores the credit field of the image
	 *
	 * @param string $credit text for the credit field
	 */
	function setCredit($credit) {
		$this->set('credit', npgFunctions::tagURLs($credit));
	}

	/**
	 * Returns the copyright field of the image
	 *
	 * @return string
	 */
	function getCopyright($locale = NULL) {
		$text = $this->get('copyright');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Stores the text for the copyright field of the image
	 *
	 * @param string $copyright text for the copyright field
	 */
	function setCopyright($copyright) {
		$this->set('copyright', npgFunctions::tagURLs($copyright));
	}

	/**
	 * Permanently delete this image (permanent: be careful!)
	 * Returns the result of the unlink operation (whether the delete was successful)
	 * @param bool $clean whether to remove the database entry.
	 * @return bool
	 */
	function remove() {
		$result = false;
		if (parent::remove()) {
			$result = true;
			$filestodelete = safe_glob(substr($this->localpath, 0, strrpos($this->localpath, '.')) . '.*');
			foreach ($filestodelete as $file) {
				chmod($file, 0777);
				$result = $result && unlink($file);
			}
			if ($result) {
				query("DELETE FROM " . prefix('obj_to_tag') . " WHERE `type`='images' AND `objectid`=" . $this->id);
				query("DELETE FROM " . prefix('comments') . " WHERE `type` ='images' AND `ownerid`=" . $this->id);
				$filestodelete = safe_glob(SERVERCACHE . '/' . substr(dirname($this->localpath), strlen(ALBUM_FOLDER_SERVERPATH)) . '/*' . stripSuffix(basename($this->localpath)) . '*');
				foreach ($filestodelete as $file) {
					chmod($file, 0777);
					$result = $result && unlink($file);
				}
			}
		}
		return $result;
	}

	/**
	 * Moves an image to a new album and/or filename (rename).
	 * Returns  0 on success and error indicator on failure.
	 * @param Album $newalbum the album to move this file to. Must be a valid Album object.
	 * @param string $newfilename the new file name of the image in the specified album.
	 * @return int
	 */
	function move($newalbum, $newfilename = null) {
		if (is_string($newalbum))
			$newalbum = newAlbum($newalbum, false);
		if ($newfilename == null) {
			$newfilename = $this->filename;
		} else {
			if (getSuffix($this->filename) != getSuffix($newfilename)) { // that is a no-no
				return 6;
			}
		}
		if ($newalbum->getID() == $this->album->getID() && $newfilename == $this->filename) {
			// Nothing to do - moving the file to the same place.
			return 2;
		}
		$newpath = $newalbum->localpath . internalToFilesystem($newfilename);
		if (file_exists($newpath)) {
			// If the file exists, don't overwrite it.
			if (!(CASE_INSENSITIVE && strtolower($newpath) == strtolower($this->localpath))) {
				return 2;
			}
		}
		$filename = basename($this->localpath);
		chmod($this->localpath, 0777);
		$result = rename($this->localpath, $newpath);
		chmod($newpath, FILE_MOD);
		if ($result) {
			$filestomove = safe_glob(substr($this->localpath, 0, strrpos($this->localpath, '.')) . '.*');
			foreach ($filestomove as $file) {
				if (in_array(strtolower(getSuffix($file)), $this->sidecars)) {
					$result = $result && rename($file, stripSuffix($newpath) . '.' . getSuffix($file));
				}
			}
			//purge the cache as it is easier than figuring out what the new cache name should be
			$filestodelete = safe_glob(SERVERCACHE . '/' . substr(dirname($this->localpath), strlen(ALBUM_FOLDER_SERVERPATH)) . '/*' . stripSuffix(basename($this->localpath)) . '*');
			foreach ($filestodelete as $file) {
				chmod($file, 0777);
				unlink($file);
			}
		}
		$this->localpath = $newpath;
		if ($result) {
			if (parent::move(array('filename' => $newfilename, 'albumid' => $newalbum->getID()))) {
				$this->set('mtime', filemtime($newpath));
				$this->save();
				return 0;
			}
		}
		return 1;
	}

	/**
	 * Renames an image to a new filename, keeping it in the same album. Convenience for move($image->album, $newfilename).
	 * Returns  true on success and false on failure.
	 * @param string $newfilename the new file name of the image file.
	 * @return bool
	 */
	function rename($newfilename) {
		return $this->move($this->album, $newfilename);
	}

	/**
	 * Copies the image to a new album, along with all metadata.
	 *
	 * @param string $newalbum the destination album
	 */
	function copy($newalbum) {
		if (is_string($newalbum)) {
			$newalbum = newAlbum($newalbum, false);
		}
		if ($newalbum->getID() == $this->album->getID()) {
			// Nothing to do - moving the file to the same place.
			return 2;
		}
		$newpath = $newalbum->localpath . internalToFilesystem($this->filename);
		if (file_exists($newpath)) {
			// If the file exists, don't overwrite it.
			return 2;
		}
		$filename = basename($this->localpath);
		$result = copy($this->localpath, $newpath);
		if ($result) {
			$filestocopy = safe_glob(substr($this->localpath, 0, strrpos($this->localpath, '.')) . '.*');
			foreach ($filestocopy as $file) {
				if (in_array(strtolower(getSuffix($file)), $this->sidecars)) {
					$result = $result && copy($file, $newalbum->localpath . basename($file));
				}
			}
		}
		if ($result) {
			if ($newID = parent::copy(array('filename' => $filename, 'albumid' => $newalbum->getID()))) {
				storeTags(readTags($this->getID(), 'images', ''), $newID, 'images');
				query('UPDATE ' . prefix('images') . ' SET `mtime`=' . filemtime($newpath) . ' WHERE `filename`="' . $filename . '" AND `albumid`=' . $newalbum->getID());
				return 0;
			}
		}
		return 1;
	}

	/*	 * ** Image Methods *** */

	/**
	 * Returns a pathurlencoded image page link for the image
	 *
	 * @return string
	 */
	function getLink() {
		if (is_array($this->filename)) {
			$albumq = dirname($this->filename['source']);
			$image = basename($this->filename['source']);
		} else {
			$albumq = $this->albumnamealbum->name;
			$image = $this->filename;
		}
		$album = $this->albumnamealbum->linkname;
		if (UNIQUE_IMAGE) {
			$image = stripSuffix($image);
		}
		return npgFilters::apply('getLink', rewrite_path(pathurlencode($album) . '/' . urlencode($image) . RW_SUFFIX, '/index.php?album=' . pathurlencode($albumq) . '&image=' . urlencode($image)), $this, NULL);
	}

	/**
	 * Returns a path to the original image in the original folder.
	 *
	 * @param string $path the "path" to the image. Defaults to the simple WEBPATH
	 *
	 * @return string
	 */
	function getImagePath($path = WEBPATH) {
		if ($path == WEBPATH && getOption('album_folder_class') == 'external') {
			return false;
		}
		if (is_array($this->filename)) {
			$album = dirname($this->filename['source']);
			$image = basename($this->filename['source']);
		} else {
			$album = $this->imagefolder;
			$image = $this->filename;
		}
		return getAlbumFolder($path) . $album . "/" . $image;
	}

	/**
	 * returns URL to the original image
	 */
	function getFullImageURL($path = WEBPATH) {
		return npgFilters::apply('getLink', pathurlencode($this->getImagePath($path)), 'full-image.php', NULL);
	}

	/**
	 * Returns a path to a sized version of the image
	 *
	 * @param int $size how big an image is wanted
	 * @return string
	 */
	function getSizedImage($size, $suffix = NULL) {
		$wmt = getWatermarkParam($this, WATERMARK_IMAGE);
		$args = getImageParameters(array('size' => $size, 'WM' => $wmt), $this->album->name);
		return getImageURI($args, $this->album->name, $this->filename, $this->filemtime, $suffix);
	}

	/**
	 *  Get a custom sized version of this image based on the parameters.
	 *
	 * @param array $args of parameters
	 * @param string suffix of imageURI
	 * @return string
	 */
	function getCustomImage($args, $suffix = NULL) {
		if (!is_array($args)) {
			$a = array('size', 'width', 'height', 'cw', 'ch', 'cx', 'cy', 'thumb', 'effects', 'suffix');
			$p = func_get_args();
			$whom = __METHOD__;
			require(PLUGIN_SERVERPATH . 'deprecated-functions/snippets/imageArguments.php');
		}
		if (!isset($args['thumb'])) {
			$args['thumb'] = NULL;
		}

		if (!isset($args['WM'])) {
			switch ((int) $args['thumb']) {
				case -1:
					$args['WM'] = '!';
					$args['thumb'] = 1;
					break;
				case 0:
					$args['WM'] = getWatermarkParam($this, WATERMARK_IMAGE);
					break;
				default:
					$args['WM'] = getWatermarkParam($this, WATERMARK_THUMB);
					break;
			}
		}
		$args = getImageParameters($args, $this->album->name);
		return getImageURI($args, $this->album->name, $this->filename, $this->filemtime, $suffix);
	}

	/**
	 * Returns the default sized image HTML
	 *
	 * @return string
	 */
	function getContent() {
		$class = 'nPGimage';
		if (!$this->getShow()) {
			$class .= " not_visible";
		}
		$album = $this->getAlbum();
		$pwd = $album->getPassword();
		if (!empty($pwd)) {
			$class .= " password_protected";
		}
		$size = getOption('image_size');
		$h = $this->getHeight();
		$w = $this->getWidth();
		$side = getOption('image_use_side');
		$us = getOption('image_allow_upscale');
		$dim = $size;

		if ($w == 0) {
			$hprop = 1;
		} else {
			$hprop = round(($h / $w) * $dim);
		}
		if ($h == 0) {
			$wprop = 1;
		} else {
			$wprop = round(($w / $h) * $dim);
		}

		if (($size && ($side == 'longest' && $h > $w) || ($side == 'height') || ($side == 'shortest' && $h < $w))) {
			// Scale the height
			$newh = $dim;
			$neww = $wprop;
		} else {
			// Scale the width
			$neww = $dim;
			$newh = $hprop;
		}
		if (!$us && $newh >= $h && $neww >= $w) {
			$neww = $w;
			$newh = $h;
		}
		$html = '<img src="' . html_encode($this->getSizedImage($size)) . '" alt="' . html_encode($this->getTitle()) . '"' .
						' width="' . $neww . '" height="' . $newh . '"' .
						(($class) ? " class=\"$class\"" : "") . " />\n";
		$html = npgFilters::apply('standard_image_html', $html, FALSE);
		if (ENCODING_FALLBACK) {
			$html = "<picture>\n<source srcset=\"" . html_encode($this->getSizedImage($size, FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
		}
		return $html;
	}

	/**
	 * Returns the image file name for the thumbnail image.
	 *
	 * @param string $path override path
	 *
	 * @return s
	 */
	function getThumbImageFile() {
		return $local = $this->localpath;
	}

	/**
	 * Returns an array of cropping parameters. Used as a "helper" function for various
	 * inherited getThumb() methods
	 *
	 * @param string $type the type of thumb (in case it ever matters in the cropping, now it does not.)
	 */
	function getThumbCropping($ts, $sw, $sh) {
		$cy = $this->get('thumbY');
		if (is_null($cy)) {
			$custom = $cx = $cw = $ch = NULL;
		} else {
			$custom = true;
			$cx = $this->get('thumbX');
			$cw = $this->get('thumbW');
			$ch = $this->get('thumbH');
			// upscale to thumb_size proportions
			if ($sw == $sh) { // square crop, set the size/width to thumbsize
				$sw = $sh = $ts;
			} else {
				if ($sw > $sh) {
					$r = $ts / $sw;
					$sw = $ts;
					$sh = $sh * $r;
				} else {
					$r = $ts / $sh;
					$sh = $ts;
					$sh = $r * $sh;
				}
			}
		}
		return array($custom, $cw, $ch, $cx, $cy);
	}

	/**
	 * Get a default-sized thumbnail of this image.
	 *
	 * @return string
	 */
	function getThumb($type = 'image', $wmt = NULL, $suffix = NULL) {
		$ts = getOption('thumb_size');
		if (getOption('thumb_crop')) {
			$sw = getOption('thumb_crop_width');
			$sh = getOption('thumb_crop_height');
			list($custom, $cw, $ch, $cx, $cy) = $this->getThumbCropping($ts, $sw, $sh);
			if ($custom) {
				return $this->getCustomImage(array('width' => $sw, 'height' => $sh, 'cw' => $cw, 'ch' => $ch, 'cx' => $cx, 'cy' => $cy, 'thumb' => TRUE));
			}
		} else {
			$sw = $sh = NULL;
		}
		if (empty($wmt)) {
			$wmt = getWatermarkParam($this, WATERMARK_THUMB);
		}
		$args = getImageParameters(array('size' => $ts, 'cw' => $sw, 'ch' => $sh, 'thumb' => TRUE, 'WM' => $wmt), $this->album->name);

		return getImageURI($args, $this->album->name, $this->filename, $this->filemtime, $suffix);
	}

	/**
	 * Get the index of this image in the album, taking sorting into account.
	 *
	 * @param object $use_album optional album to override if image is within a dynamic album
	 * @return int
	 */
	function getIndex($use_album = null) {
		global $_current_search;
		if ($this->index == NULL || $use_album) {
			if ($use_album) {
				$album = $use_album;
			} else {
				$album = $this->albumnamealbum;
			}
			$filename = $this->filename;
			if ($album->isDynamic() || $_current_search && !in_context(ALBUM_LINKED)) {
				$imagefolder = $this->imagefolder;
				if ($album->isDynamic()) {
					$images = $album->getImages(0);
				} else {
					$images = $_current_search->getImages(0);
				}
				$target = array_filter($images, function ($item) use ($filename, $imagefolder) {
					return $item['filename'] == $filename && $item['folder'] == $imagefolder;
				});
			} else {
				$images = $this->album->getImages(0);
				$target = array_filter($images, function ($item) use ($filename) {
					return $item == $filename;
				});
			}
			$index = key($target);

			if ($use_album) {
				//	don't set the property of the album isn't the same as the instantiation album
				return $index;
			}
			$this->index = $index;
		}
		return $this->index;
	}

	/**
	 * Returns the next Image.
	 *
	 * @return object
	 */
	function getNextImage() {
		global $_current_search;
		$index = $this->getIndex();
		$album = $this->albumnamealbum;
		if ($album->isDynamic() || is_null($_current_search) || in_context(ALBUM_LINKED)) {
			$image = $album->getImage($index + 1);
			if ($image && $image->exists && $album->isDynamic()) {
				$image->albumname = $album->name;
				$image->albumlink = $album->linkname;
				$image->albumnamealbum = $album;
			}
		} else {
			$image = $_current_search->getImage($index + 1);
		}
		return $image;
	}

	/**
	 * Return the previous Image
	 *
	 * @return object
	 */
	function getPrevImage() {
		global $_current_search;
		$album = $this->albumnamealbum;
		$index = $this->getIndex();
		if ($album->isDynamic() || is_null($_current_search) || in_context(ALBUM_LINKED)) {
			$image = $album->getImage($index - 1);
			if ($image && $image->exists && $album->isDynamic()) {
				$image->albumname = $album->name;
				$image->albumlink = $album->linkname;
				$image->albumnamealbum = $album;
			}
		} else {
			$image = $_current_search->getImage($index - 1);
		}

		return $image;
	}

	/**
	 * Returns the custom watermark name
	 *
	 * @return string
	 */
	function getWatermark() {
		return $this->get('watermark');
	}

	/**
	 * Set custom watermark
	 *
	 * @param string $wm
	 */
	function setWatermark($wm) {
		$this->set('watermark', $wm);
	}

	/**
	 * Returns the custom watermark usage
	 *
	 * @return bool
	 */
	function getWMUse() {
		return $this->get('watermark_use');
	}

	/**
	 * Sets the custom watermark usage
	 *
	 * @param $use
	 */
	function setWMUse($use) {
		$this->set('watermark_use', $use);
	}

	/**
	 * Owner functions
	 */
	function getOwner() {
		$owner = $this->get('owner');
		if (empty($owner)) {
			$owner = $this->album->getOwner();
		}
		return $owner;
	}

	function isMyItem($action) {
		if (npg_loggedin($action)) {
			if ($action == LIST_RIGHTS && $this->isPublished()) {
				return true;
			}
		}
		$album = $this->album;
		return $album->isMyItem($action);
	}

	/**
	 * returns true if user is allowed to see the image
	 */
	function checkAccess(&$hint = NULL, &$show = NULL) {
		$album = $this->getAlbum();
		if ($album->isMyItem(LIST_RIGHTS)) {
			return $this->isPublished() || $album->subRights() & (MANAGED_OBJECT_RIGHTS_EDIT | MANAGED_OBJECT_RIGHTS_VIEW);
		}
		return $album->checkforGuest($hint, $show) && $this->isPublished();
	}

	/**
	 * Checks if guest is loggedin for the album
	 * @param unknown_type $hint
	 * @param unknown_type $show
	 */
	function checkforGuest(&$hint = NULL, &$show = NULL) {
		if (!parent::checkForGuest($hint, $show)) {
			return false;
		}
		$album = $this->getAlbum();
		return $album->checkforGuest($hint, $show);
	}

	/**
	 *
	 * returns true if there is any protection on the image
	 */
	function isProtected() {
		return $this->checkforGuest() != 'public_access';
	}

	/**
	 * checks if the album and its parents are published
	 * @return boolean
	 */
	function isPublished() {
		if ($this->getShow()) {
			if ($parent = $this->getAlbum()) {
				return $parent->isPublished();
			}
			return TRUE;
		}
		return FALSE;
	}

	function isPhoto() {
		switch (get_class($this)) {
			case'Image':
			case 'Transientimage':
				return true;
		}
		return false;
	}

	function isVideo() {
		return false;
	}

	/**
	 * provides aspect ration string for a given width and height.
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $separator
	 */
	static function aspectRatio($imageWidth, $imageHeight, $separator = ':') {
		$divisor = (int) gmp_gcd($imageWidth, $imageHeight);
		return $imageWidth / $divisor . ':' . $imageHeight / $divisor;
	}

	/**
	 * Returns the filesize in bytes of the full image
	 *
	 * @return int|false
	 */
	function getFilesize() {
		return filesize($this->localpath);
	}

}

class Transientimage extends Image {

	/**
	 * creates a transient image (that is, one that is not stored in the database)
	 *
	 * @param string $image the full path to the image
	 * @return transientimage
	 */
	function __construct($album, $image) {
		if (!is_object($album)) {
			$album = new AlbumBase('Transient');
		}
		$this->album = $this->albumnamealbum = $album;
		$this->localpath = $image;
		$filename = makeSpecialImageName($image);
		$this->filename = $filename;
		$this->displayname = stripSuffix(basename($image));
		if (empty($this->displayname)) {
			$this->displayname = $this->filename['name'];
		}
		$this->filemtime = filemtime($this->localpath);
		$this->comments = null;
		$this->instantiate('images', array('filename' => $filename['name'], 'albumid' => $this->album->getID()), 'filename', true, true);
		$this->exists = false;
	}

}

?>
