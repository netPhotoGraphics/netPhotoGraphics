<?php

/**
 * Core class for handling "non-image" files
 *
 *
 * @author Stephen Billard (sbillard)
 * @package plugins/class-textobject
 *
 */
class TextObject_core extends Image {

	protected $watermark = NULL;
	protected $watermarkDefault = NULL;

	/**
	 * @param object $album the owner album
	 * @param string $filename the filename of the text file
	 * @return TextObject
	 */
	function __construct($album, $filename, $quiet = false) {
		$this->common_instantiate($album, $filename, $quiet);
	}

	/**
	 * returns the database fields used by the object
	 * @return array
	 *
	 * @author Stephen Billard
	 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
	 */
	static function getMetadataFields() {
		return array();
	}

	/**
	 * Handles class common instantiation
	 * @param $album
	 * @param $filename
	 */
	function common_instantiate($album, $filename, $quiet = false) {
		global $_supported_images;
		$msg = $this->invalid($album, $filename);
		if ($msg) {
			$this->exists = false;
			if (!$quiet) {
				debugLogBacktrace($msg);
			}
			return;
		}
		$this->sidecars = $_supported_images;
		$this->objectsThumb = checkObjectsThumb($this->localpath);
		$this->updateDimensions();
		$new = $this->instantiate('images', array('filename' => $filename, 'albumid' => $this->album->getID()), 'filename');
		if ($new || $this->filemtime != $this->get('mtime')) {
			if ($new)
				$this->setTitle($this->displayname);
			$title = $this->displayname;
			$this->updateMetaData();
			$this->set('mtime', $this->filemtime);
			$this->set('filesize', filesize($this->localpath));
			$this->save();
			if ($new)
				npgFilters::apply('new_image', $this);
		}
	}

	/**
	 * Returns the image file name for the thumbnail image.
	 *
	 * @return string
	 */
	function getThumbImageFile() {
		global $_gallery;

		$path = SERVERPATH;
		if (is_null($this->objectsThumb)) {
			switch (getSuffix($this->filename)) {
				case 'txt':
				case 'htm':
				case 'html':
				default: // just in case we extend and are lazy...
					$img = '/textDefault.png';
					break;
				case 'pdf':
					$img = '/pdfDefault.png';
					break;
			}
			$imgfile = $path . '/' . THEMEFOLDER . '/' . internalToFilesystem($_gallery->getCurrentTheme()) . '/images/' . $img;
			if (!file_exists($imgfile)) {
				$imgfile = $path . "/" . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/class-textobject/' . $img;
			}
		} else {
			$imgfile = dirname($this->localpath) . '/' . $this->objectsThumb;
		}
		return $imgfile;
	}

	/**
	 * returns a link to the thumbnail for the text file.
	 *
	 * @param string $type 'image' or 'album'
	 * @return string
	 */
	function getThumb($type = 'image', $wmt = NULL, $suffix = NULL) {
		$ts = getOption('thumb_size');
		$crop = false;
		if (getOption('thumb_crop')) {
			$crop = true;
			$sw = getOption('thumb_crop_width');
			$sh = getOption('thumb_crop_height');
			list($custom, $cw, $ch, $cx, $cy) = $this->getThumbCropping($ts, $sw, $sh);
		} else {
			$crop = false;
			$sw = $sh = $cw = $ch = $cx = $cy = null;
		}
		if (empty($wmt)) {
			$wmt = $this->watermark;
		}
		if (empty($wmt)) {
			$wmt = getWatermarkParam($this, WATERMARK_THUMB);
		}

		if ($this->objectsThumb == NULL) {
			$mtime = $cx = $cy = NULL;
			$filename = makeSpecialImageName($this->getThumbImageFile());
			if (!$this->watermarkDefault) {
				$wmt = '!';
			}
		} else {
			$filename = filesystemToInternal($this->objectsThumb);
			$mtime = filemtime(dirname($this->localpath) . '/' . $filename);
		}
		$args = array('size' => $ts, 'width' => $sw, 'height' => $sh, 'cw' => $cw, 'ch' => $ch, 'cx' => $cx, 'cy' => $cy, 'crop' => $crop, 'thumb' => TRUE, 'WM' => $wmt);
		$args = getImageParameters($args, $this->album->name);
		return getImageURI($args, $this->album->name, $filename, $mtime, $suffix);
	}

	/**
	 * Returns the content of the text file
	 *
	 * @param int $w optional width
	 * @param int $h optional height
	 * @return string
	 */
	function getContent($w = NULL, $h = NULL) {
		$this->updateDimensions();
		if (is_null($w))
			$w = $this->getWidth();
		if (is_null($h))
			$h = $this->getHeight();
		switch (getSuffix($this->filename)) {
			case 'txt':
			case 'htm':
			case 'html':
				return '<div style="display:block;width:' . $w . 'px;height:' . $h . 'px;" class="textobject">' . file_get_contents($this->localpath) . '</div>';
			case 'pdf':
				return '<div style="background-image: url(\'' . html_encode($this->getCustomImage(array('size' => min($w, $h), 'thumb' => 3, 'WM' => 'err-broken-page'))) . '\'); background-repeat: no-repeat; background-position: center;" >' .
								'<iframe src="' .
								html_encode($this->getFullImageURL(FULLWEBPATH)) .
								'" width="' . $w . 'px" height="' . $h . 'px" frameborder="0" border="none" scrolling="auto" class="textobject"></iframe>' .
								'</div>';
			default: // just in case we extend and are lazy...
				$s = min($w, $h);
				return '<img src="' . html_encode($this->getCustomImage(array('size' => $s, 'thumb' => 3, 'WM' => 'err-broken-page'))) . '" class="' . get_class($this) . '_default" width=' . $s . ' height=' . $s . '>';
				;
		}
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
			$args = array();
			foreach ($p as $k => $v) {
				$args[$a[$k]] = $v;
			}
			if (isset($args['suffix'])) {
				$suffix = $args['suffix'];
				unset($args['suffix']);
			} else {
				$suffix = NULL;
			}
			$example = '';
			foreach ($args as $arg => $v) {
				if (!is_null($v)) {
					$example .= ",'" . $arg . "'=>";
					if (is_numeric($v)) {
						$example .= $v;
					} else if (is_bool($v)) {
						if ($v) {
							$example .= 'true';
						} else {
							$example .= 'false';
						}
					} else {
						$example .= "'" . $v . "'";
					}
				}
			}
			$example = '[' . ltrim($example, ',') . ']';
			require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
			deprecated_functions::notify_call('TextObject::getCustomImage', gettext('The function should be called with an image arguments array.') . sprintf(gettext('e.g. %1$s '), $example));
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
				case 3:
					//	use thumb image as full sized image (posters, etc.
					$args['WM'] = getWatermarkParam($this, WATERMARK_IMAGE);
					break;
				default:
					if (empty($this->watermark)) {
						$args['WM'] = getWatermarkParam($this, WATERMARK_THUMB);
					} else {
						$args['WM'] = $this->watermark;
					}
					break;
			}
		}

		if ($args['thumb']) {
			if ($this->objectsThumb == NULL) {
				$filename = makeSpecialImageName($this->getThumbImageFile());
				if (!$this->watermarkDefault) {
					$args['WM'] = '!';
				}
				$mtime = NULL;
			} else {
				$filename = filesystemToInternal($this->objectsThumb);
				$mtime = filemtime(dirname($this->localpath) . '/' . $this->objectsThumb);
			}

			$args = getImageParameters($args, $this->album->name);
			return getImageURI($args, $this->album->name, $filename, $mtime, $suffix);
		} else {
			return $this->getContent($args['width'], $args['height']);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Image::getSizedImage()
	 */
	function getSizedImage($size, $suffix = NULL) {
		switch (getOption('image_use_side')) {
			case 'width':
			case 'longest':
				$w = $size;
				$h = floor(($size * 24) / 36);
				break;
			case 'height':
			case 'shortest':
				$h = $size;
				$w = floor(($size * 36) / 24);
				break;
		}

		return $this->getContent($w, $h);
	}

	/**
	 * (non-PHPdoc)
	 * @see Image::updateDimensions()
	 */
	function updateDimensions() {
		$size = getOption('image_size');
		switch (getOption('image_use_side')) {
			case 'width':
			case 'longest':
				$this->set('width', getOption('image_size'));
				$this->set('height', floor((getOption('image_size') * 24) / 36));
				break;
			case 'height':
			case 'shortest':
				$this->set('height', getOption('image_size'));
				$this->set('width', floor((getOption('image_size') * 36) / 24));
				break;
		}
	}

}

?>