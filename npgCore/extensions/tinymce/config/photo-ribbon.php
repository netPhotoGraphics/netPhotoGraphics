<?php

/**
 * The configuration parameters for TinyMCE.
 *
 * ribbon-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.texteditor";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks  code fullscreen " .
				"insertdatetime media nonbreaking save " .
				"emoticons directionality help";

$MCEtoolbars = array();
$MCEstatusbar = true;
$MCEmenubar = true;
include(TINYMCE . '/config/config.js.php');
