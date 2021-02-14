<?php

/*
 * Example extension to the AnyFile plugin. Overrides the TextObject handling of pdf files
 */

class Pdf extends AnyFile {

	function getContent($w = NULL, $h = NULL) {
		$this->updateDimensions();
		if (is_null($w))
			$w = $this->getWidth();
		if (is_null($h))
			$h = $this->getHeight();

		return '<div style="background-image: url(\'' . html_encode($this->getCustomImage(array('size' => min($w, $h), 'thumb' => 3, 'WM' => 'err-broken-page'))) . '\'); background-repeat: no-repeat; background-position: center;" >' .
						'<iframe src="' .
						html_encode($this->getFullImageURL(FULLWEBPATH)) .
						'" width="' . $w . 'px" height="' . $h . 'px" frameborder="0" border="none" scrolling="auto" class="AnyFile-Pdf"></iframe>' .
						'</div>';
	}

}
