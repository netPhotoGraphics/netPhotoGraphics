<?php

/**
 * The configuration parameters for TinyMCE.
 *
 * full-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.texteditor";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks code fullscreen " .
				"insertdatetime media nonbreaking save table directionality " .
				"emoticons help";
$MCEtoolbars[1] = "undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image";
$MCEtoolbars[2] = "media emoticons nonbreaking pagebreak | ltr rtl | forecolor backcolor | code fullscreen";
$MCEstatusbar = true;
$MCEmenubar = true;
include(TINYMCE . '/config/config.js.php');
