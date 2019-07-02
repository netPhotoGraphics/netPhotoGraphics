<?php

/**
 * library for image handling using the GD library of functions
 * @package core
 */
// force UTF-8 Ø

$_graphics_optionhandlers[] = new lib_NoGraphics(); // register option handler
/**
 * Option class for lib-GD
 *
 */

class lib_NoGraphics {

	function __construct() {

	}

	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {

	}

	function canLoadMsg() {
		return '';
	}

}

if (!function_exists('gl_graphicsLibInfo')) {

	$_lib_GD_info = array();
	$_lib_GD_info['Library'] = 'None';
	$_lib_GD_info['Library_desc'] = '<p class="error">' . gettext('There is no PHP Graphics support.') . '</p>';

	if (DEBUG_IMAGE)
		debugLog($_lib_GD_info['Library_desc']);

	function gl_imageGet($imgfile) {
		return false;
	}

	function gl_imageOutputt($im, $type, $filename = NULL, $qual = 75) {
		return false;
	}

	function gl_createImage($w, $h) {
		return false;
	}

	function gl_imageFill($image, $x, $y, $color) {
		return false;
	}

	function gl_imageColorTransparent($image, $color) {
		return false;
	}

	function gl_copyCanvas($imgCanvas, $img, $dest_x, $dest_y, $src_x, $src_y, $w, $h) {
		return false;
	}

	function gl_resampleImage($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		return false;
	}

	function gl_imageUnsharpMask($img, $amount, $radius, $threshold) {
		return false;
	}

	function gl_imageResizeAlpha($src, $w, $h) {
		return false;
	}
	
	function Gl_imageResizeTransparent($src, $w, $h) {
		return false;
	}

	function gl_imageCanRotate() {
		return false;
	}

	function gl_rotateImage($im, $rotate) {
		return false;
	}

	function gl_imageDims($filename) {
		return false;
	}

	function gl_imageIPTC($filename) {
		return false;
	}

	function gl_imageWidth($im) {
		return false;
	}

	function gl_imageHeight($im) {
		return false;
	}

	function gl_imageMerge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
		return false;
	}

	function gl_imageGray($image) {

	}

	function gl_imageKill($im) {
		return false;
	}

	function gl_colorAllocate($image, $red, $green, $blue) {
		return false;
	}

	function gl_writeString($image, $font, $x, $y, $string, $color) {

	}

	function gl_drawRectangle($image, $x1, $y1, $x2, $y2, $color) {
		return false;
	}

	function gl_graphicsLibInfo() {
		global $_lib_GD_info;
		return $_lib_GD_info;
	}

	function gl_getFonts() {
		return $_gd_fontlist;
	}

	function gl_imageLoadFont($font = NULL, $size = 18) {
		return false;
	}

	function gl_imageFontWidth($font) {
		return false;
	}

	function gl_imageFontHeight($font) {
		return false;
	}

	function imageBlurGD($imgCanvas, $imgCanvas2, $radius, $w, $h) {

	}

	function gl_imageFromString($string) {
		return false;
	}

}
?>