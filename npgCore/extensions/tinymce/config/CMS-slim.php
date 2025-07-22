<?php

/**
 * The configuration parameters for TinyMCE.
 *
 * CMS plugin slim-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.content,textarea.desc,textarea.extracontent";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars visualblocks code fullscreen " .
				"insertdatetime media table pasteobj directionality help";
$MCEtoolbars[1] = "styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image pasteobj | ltr rtl code fullscreen";
$MCEstatusbar = true;
$MCEmenubar = false;
include(TINYMCE . '/config/config.js.php');
