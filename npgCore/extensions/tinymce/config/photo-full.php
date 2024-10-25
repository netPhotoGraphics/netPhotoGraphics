<?php

/**
 * The configuration functions for TinyMCE
 *
 * full-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.texteditor";
$MCEplugins = "advlist autolink lists link image charmap hr anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks code fullscreen " .
				"imagetools colorpicker textcolor " .
				"insertdatetime media nonbreaking save table directionality " .
				"emoticons template paste ";
$MCEtoolbars[1] = "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image";
$MCEtoolbars[2] = "media emoticons nonbreaking pagebreak | ltr rtl | forecolor backcolor | code fullscreen";
$MCEstatusbar = true;
$MCEmenubar = true;
include(TINYMCE . '/config/config.js.php');
