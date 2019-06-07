<?php

// force UTF-8 Ø

if (!OFFSET_PATH) {

	// override some options to avoid conflits
	setOption('comment_form_toggle', false, true);
	setOption('comment_form_pagination', false, true);
	setOption('tinymce_comments', null, true);
	setOption('user_logout_login_form', 1, true);
	setOption('gmap_display', 'show', true);

	// Check for mobile and tablets, and set some options
	require_once (CORE_SERVERPATH .  PLUGIN_FOLDER . '/mobileTheme/Mobile_Detect.php');
	$detect = new Mobile_Detect;

	if ($detect->isTablet()) {
		$isTablet = true;
	} else {
		$isTablet = false;
	}
	if (($detect->isMobile()) && (!$detect->isTablet())) {
		$isMobile = true;
	} else {
		$isMobile = false;
	}

	if ($isMobile) {
		// set album thumb size and album thumb size for mobile device
		setOption('zpB_album_thumb_width', 720, false);
		setOption('zpB_album_thumb_height', 360, false);

		setOption('thumb_size', 350, false);
		setOption('thumb_crop_width', 350, false);
		setOption('thumb_crop_height', 350, false);

		// set shorten title size
		$zpB_shorten_title_size = 26;
	} else {
		// set album thumb size and album thumb size
		setOption('zpB_album_thumb_width', 360, false);
		setOption('zpB_album_thumb_height', 180, false);

		setOption('thumb_size', 220, false);
		setOption('thumb_crop_width', 220, false);
		setOption('thumb_crop_height', 220, false);

		// set shorten title size
		$zpB_shorten_title_size = 50;
	}

	$_current_page_check = 'my_checkPageValidity';

	$_zenpage_enabled = extensionEnabled('zenpage');
	$_zenpage_news_enabled = extensionEnabled('zenpage') && hasNews();
	$_zenpage_pages_enabled = extensionEnabled('zenpage') && hasPages();
	/* if ($_zenpage_pages_enabled && is_Pages() && (getPageTitleLink() == 'guestbook')) {
	  setOption('comment_form_addresses', 1, false);
	  } */
}

function my_checkPageValidity($request, $gallery_page, $page) {
	if (($gallery_page == 'gallery.php') || ($gallery_page == 'home.php')) {
		$gallery_page = 'index.php';
	}
	return checkPageValidity($request, $gallery_page, $page);
}

/**
 * Returns different random pictures from gallery or an album
 * If there are less pictures as requested, returns this number of pictures
 * @param int $number Number of random pictures to return (default is 5)
 * @param string $option 'all' for gallery, else 'album' for an album (default is 'all')
 * @param string $album_filename full filename of album to use
 * @return an array of pictures, or false is there is no picture to return
 */
function zpB_getRandomImages($number = 5, $option = 'all', $album_filename = '') {
	global $_gallery;

	switch ($option) {
		case "all" :
			$number_max = $_gallery->getNumImages(2);
			break;
		case "album" :
			if (!empty($album_filename)) {
				$album = newAlbum($album_filename);
				$number_max = $album->getNumImages();
			}
			break;
	}

	$number = min($number, $number_max);
	$randomImageList = array();

	$i = 1;
	while ($i <= $number) {
		switch ($option) {
			case "all" :
				$randomImage = getRandomImages();
				break;
			case "album" :
				$randomImage = getRandomImagesAlbum($album_filename);
				break;
		}
		if ((is_object($randomImage)) && ($randomImage->exists)) {
			if (array_search($randomImage, $randomImageList) === false) {
				$randomImageList[] = $randomImage;
				$i++;
			}
		} else {
			break;
		}
	}
	if (!empty($randomImageList)) {
		return $randomImageList;
	} else {
		return false;
	}
}

/**
 * Returns true if there is a next news page and false if there is not
 * @return bool
 */
function zpB_hasNextNewsPage() {
	global $_CMS, $_current_page;

	$total_pages = getTotalNewsPages();
	if ($_current_page < $total_pages) {
		return true;
	} else {
		return false;
	}
}

/**
 * Prints the URL of the next news page.
 *
 * @param string $text text for the URL
 * @param string $class Text for the HTML class
 */
function zpB_printNextNewsPageURL($text, $class = NULL) {
	global $_CMS, $_current_page;

	if (zpB_hasNextNewsPage()) {
		echo '<a href="' . getNextNewsPageURL() . '" class="' . $class . '" >' . html_encode($text) . '</a>';
	} else {
		echo '<span class="disabledlink">' . html_encode($text) . '</span>';
	}
}

/**
 * Returns the source link of the video in the txt/htm/html file
 *
 * @param string $content content of a text file supposed to describe a link to an online video
 * @return string or false is there is no iframe with a link
 */
function zpB_getLink($content) {

	$link = false;
	$iframepattern = '/<iframe/';
	$urlpattern = '/src="([^"]+)"/';
	if (preg_match($iframepattern, $content)) {
		if (preg_match($urlpattern, $content, $result)) {
			$link = pathurlencode($result[1]);
		}
	}
	return $link;
}

?>