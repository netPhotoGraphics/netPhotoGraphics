<?php

/**
 * The configuration parameters for TinyMCE.
 *
 * Comment form ribbon-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.textarea_inputbox, textarea.texteditor_comments";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks code fullscreen " .
				"insertdatetime media nonbreaking save table directionality " .
				"emoticons help";
$MCEtoolbars = array();
$MCEstatusbar = boolval(OFFSET_PATH);
$MCEmenubar = true;
include(TINYMCE . '/config/config.js.php');
