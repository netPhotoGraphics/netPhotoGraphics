<?php

/**
 * creates the CAPTCHA images
 * @package core/captcha
 */
// force UTF-8 Ø
define('OFFSET_PATH', 3);
require_once('../../functions.php');

header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header("Content-type: image/png");
$cypher = preg_replace('/[^0-9a-f]/', '', sanitize(isset($_GET['i']) ? $_GET['i'] : NULL));

$key = getOption('npg_captcha_key');
$string = rc4($key, pack("H*", $cypher));
$len = getOption('npg_captcha_length');
$string = str_pad($string, $len - strlen($string), '*');

if (isset($_GET['f'])) {
	$fontname = sanitize($_GET['f'], 3);
} else {
	$fontname = getOption('npg_captcha_font');
	if ($fontname == '*') { //	Random selection
		$fonts = gl_getFonts();
		shuffle($fonts);
		$fontname = array_shift($fonts);
	}
}


if (isset($_GET['p'])) {
	$size = sanitize_numeric($_GET['p']);
} else {
	$size = getOption('npg_captcha_font_size');
}

$font = gl_imageLoadFont($fontname, $size);

$pallet = array(
		array('R' => 16, 'G' => 110, 'B' => 3),
		array('R' => 132, 'G' => 4, 'B' => 16),
		array('R' => 103, 'G' => 3, 'B' => 143),
		array('R' => 143, 'G' => 32, 'B' => 3),
		array('R' => 143, 'G' => 38, 'B' => 48),
		array('R' => 0, 'G' => 155, 'B' => 18));
$fw = gl_imageFontWidth($font);
$fh = gl_imageFontHeight($font);

if (strtoupper(getSuffix($fontname)) == 'TTF') {
	$leadOffset = - $fh / 4;
	$kernOffset = $fw;
} else {
	$leadOffset = 0;
	$kernOffset = 0;
}
$w = 0;
$h = $fh = gl_imageFontHeight($font);
$kerning = min(5, floor($fw / 4) - 1);
$leading = $fh / 2 - 4;
$ink = $lead = $kern = array();
for ($i = 0; $i < $len; $i++) {
	$lead[$i] = rand(0, $leading);
	$h = max($h, $fh + $lead[$i] + 5);
	$kern[$i] = rand(-$kerning, $kerning);
	$w = $w + $kern[$i] + $fw;
	$p[$i] = $pallet[rand(0, 5)];
}

$w = $w + 5;
$image = gl_createImage($w, $h);
$background = gl_imageGet(CORE_SERVERPATH . PLUGIN_FOLDER . '/captcha/captcha_background.png');
gl_copyCanvas($image, $background, 0, 0, rand(0, 9), rand(0, 9), $w, $h);

$l = $kern[0] - $kernOffset;
for ($i = 0; $i < $len; $i++) {
	$ink = gl_colorAllocate($image, $p[$i]['R'], $p[$i]['G'], $p[$i]['B']);
	gl_writeString($image, $font, $l, $lead[$i] + $leadOffset, $string{$i}, $ink, rand(-10, 10));
	$l = $l + $fw + $kern[$i];
}

$rectangle = gl_colorAllocate($image, 48, 57, 85);
gl_drawRectangle($image, 0, 0, $w - 1, $h - 1, $rectangle);

gl_imageOutputt($image, 'png', NULL);
?>

