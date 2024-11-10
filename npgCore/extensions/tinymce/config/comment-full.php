<?php

/**
 * The configuration parameters for TinyMCE.
 *
 * Comment form full-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.textarea_inputbox, textarea.texteditor_comments";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks code fullscreen " .
				"insertdatetime media nonbreaking save table " .
				"emoticons directionality help";
$MCEtoolbars[1] = "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image";
$MCEtoolbars[2] = "media emoticons nonbreaking pagebreak | ltr rtl | forecolor backcolor | code fullscreen";
$MCEstatusbar = boolval(OFFSET_PATH);
$MCEmenubar = true;
include(TINYMCE . '/config/config.js.php');
