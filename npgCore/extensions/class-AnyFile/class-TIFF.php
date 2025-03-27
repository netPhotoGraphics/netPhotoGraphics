<?php

/*
 * Example extension to the AnyFile plugin. Allows native viewing of TIFF files (presuming
 * the browser supports TIFF image sources.)
 */

Gallery::addImageHandler('tif', 'Tiff');

class Tiff extends AnyFile {

	function getContent($w = NULL, $h = NULL) {
		$this->updateDimensions();
		if (is_null($w))
			$w = $this->getWidth();
		if (is_null($h))
			$h = $this->getHeight();

		return '<img src="' . html_encode($this->getFullImageURL(FULLWEBPATH)) . '" width="' . $w . 'px" height="' . $h . 'px"  class="AnyFile-Tiff" >';
	}

	function getThumbImageFile() {
		global $_gallery;
		if (is_null($this->objectsThumb)) {
			$img = '/tiffDefault.png';
			$imgfile = SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem($_gallery->getCurrentTheme()) . '/images/' . $img;
			if (!file_exists($imgfile)) {
				$imgfile = SERVERPATH . "/" . USER_PLUGIN_FOLDER . '/class-AnyFile' . $img;
				if (!file_exists($imgfile)) {
					$imgfile = SERVERPATH . "/" . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/class-AnyFile/Default.png';
				}
			}
		} else {
			$imgfile = dirname($this->localpath) . '/' . $this->objectsThumb;
		}
		return $imgfile;
	}

}
