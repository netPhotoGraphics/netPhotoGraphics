<?php

/**
 * The configuration parameters for TinyMCE 4.x.
 *
 * Comment form slim-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.content,textarea.desc,textarea.extracontent";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars visualblocks code fullscreen " .
				"insertdatetime media table directionality help";
$MCEtoolbars[1] = "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image searchreplace visualchars | ltr rtl";
$MCEstatusbar = boolval(OFFSET_PATH);
$MCEmenubar = false;
include(TINYMCE . '/config/config.js.php');
