<?php

/**
 * The configuration parameters for TinyMCE 4.x.
 *
 * zenphoto slim-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.texteditor";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars visualblocks code fullscreen " .
				"insertdatetime media paste directionality ";
$MCEtoolbars[1] = "styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | ltr rtl code";
$MCEstatusbar = false;
$MCEmenubar = false;
include(TINYMCE . '/config/config.js.php');
