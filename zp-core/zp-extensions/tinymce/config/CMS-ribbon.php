<?php

/**
 * The configuration functions for TinyMCE
 *
 * Zenpage plugin ribbon-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.content,textarea.desc,textarea.extracontent";
$MCEplugins = "advlist autolink lists link image charmap hr anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks  code fullscreen " .
				"insertdatetime media nonbreaking save table directionality " .
				"emoticons template paste pasteobj ";
$MCEtoolbars = array();
$MCEstatusbar = true;
$MCEmenubar = true;
include(TINYMCE . '/config/config.js.php');
