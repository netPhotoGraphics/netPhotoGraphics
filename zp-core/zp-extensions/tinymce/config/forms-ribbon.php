<?php

/**
 * The configuration functions for TinyMCE
 *
 * zenphoto ribbon-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.texteditor";
$MCEplugins = "advlist autolink lists link image charmap hr anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks  code fullscreen " .
				"insertdatetime media nonbreaking save " .
				"emoticons template paste directionality ";

$MCEtoolbars = array();
$MCEstatusbar = true;
$MCEmenubar = true;
include(TINYMCE . '/config/config.js.php');
